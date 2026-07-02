<?php

namespace App\Controller;

use App\Entity\Animal;
use App\Entity\User;
use App\Form\AnimalType;
use App\Repository\AnimalRepository;
use App\Service\DocumentUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/animal')]
#[IsGranted('ROLE_USER')]
final class AnimalController extends AbstractController
{
    public function __construct(
        private readonly DocumentUploader $documentUploader,
        private readonly SluggerInterface $slugger,

        #[Autowire('%kernel.project_dir%/var/uploads/animals')]
        private readonly string $animalUploadsDirectory,

        #[Autowire('%kernel.project_dir%/public/uploads/photos')]
        private readonly string $photoDirectory,
    ) {
    }

    #[Route('/', name: 'app_animal_index', methods: ['GET'])]
    public function index(AnimalRepository $animalRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $animals = $animalRepository->findAll();
        } else {
            $animals = $animalRepository->findAccessibleAnimals($user);
        }

        return $this->render('animal/index.html.twig', [
            'animals' => $animals,
        ]);
    }

    #[Route('/new', name: 'app_animal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $animal = new Animal();

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $animal->setOwner($user);

            $this->handlePhotoUpload($form, $animal);
            $this->handleUploadedDocuments($form, $animal);

            $entityManager->persist($animal);
            $entityManager->flush();

            return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('animal/new.html.twig', [
            'animal' => $animal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_animal_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Animal $animal): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $animal);

        return $this->render('animal/show.html.twig', [
            'animal' => $animal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_animal_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Animal $animal, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $animal);

        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->handlePhotoUpload($form, $animal);
            $this->handleUploadedDocuments($form, $animal);

            $entityManager->flush();

            return $this->redirectToRoute('app_animal_show', [
                'id' => $animal->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('animal/edit.html.twig', [
            'animal' => $animal,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_animal_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Request $request, Animal $animal, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_DELETE', $animal);

        if ($this->isCsrfTokenValid('delete' . $animal->getId(), $request->getPayload()->getString('_token'))) {
            if ($animal->getPhoto()) {
                $photoPath = $this->photoDirectory . '/' . $animal->getPhoto();
                if (is_file($photoPath)) {
                    unlink($photoPath);
                }
            }

            $this->deletePhysicalDocuments($animal->getDocuments());

            $entityManager->remove($animal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
    }

    private function handlePhotoUpload(FormInterface $form, Animal $animal): void
    {
        /** @var UploadedFile|null $photoFile */
        $photoFile = $form->get('photoFile')->getData();

        if (!$photoFile) {
            return;
        }

        if ($animal->getPhoto()) {
            $oldPath = $this->photoDirectory . '/' . $animal->getPhoto();
            if (is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        if (!is_dir($this->photoDirectory)) {
            mkdir($this->photoDirectory, 0775, true);
        }

        $safeFilename = $this->slugger->slug(pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME));
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

        $photoFile->move($this->photoDirectory, $newFilename);
        $animal->setPhoto($newFilename);
    }

    private function handleUploadedDocuments(FormInterface $form, Animal $animal): void
    {
        if (!$form->has('attachments')) {
            return;
        }

        $uploadedFiles = $form->get('attachments')->getData();

        if (empty($uploadedFiles)) {
            return;
        }

        $documents = $this->documentUploader->uploadMany($uploadedFiles, $this->animalUploadsDirectory);

        foreach ($documents as $document) {
            $animal->addDocument($document);
        }
    }

    private function deletePhysicalDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            if (!isset($document['fileName'])) {
                continue;
            }

            $filePath = $this->animalUploadsDirectory . '/' . $document['fileName'];

            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }
}
