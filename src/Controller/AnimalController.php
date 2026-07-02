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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/animal')]
final class AnimalController extends AbstractController
{
    public function __construct(
        private readonly DocumentUploader $documentUploader,

        #[Autowire('%kernel.project_dir%/public/uploads/animals')]
        private readonly string $animalUploadsDirectory,
    ) {
    }

    #[Route('/', name: 'app_animal_index', methods: ['GET'])]
    public function index(AnimalRepository $animalRepository): Response
    {
        // 🔒 Récupère l'utilisateur connecté
        /** @var User $user */
        $user = $this->getUser();

        // 🛡️ Si l'utilisateur est un Administrateur, il peut tout voir (optionnel)
        if ($this->isGranted('ROLE_ADMIN')) {
            $animals = $animalRepository->findAll();
        } else {
            // 🔑 Un utilisateur classique ne voit QUE ses animaux
            $animals = $animalRepository->findBy(['owner' => $user]);
        }

        return $this->render('animal/index.html.twig', [
            'animals' => $animals,
        ]);
    }

    #[Route('/new', name: 'app_animal_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $animal = new Animal();

        // 🔒 Sécurité : On récupère l'utilisateur connecté
        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 🔑 On associe l'animal à cet utilisateur précis
            $animal->setOwner($user);

            $entityManager->persist($animal);
            $entityManager->flush();

            return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
        }

        // 💡 C'EST CE RETURN ICI QU'IL VOUS MANQUE :
        // Il permet d'afficher la page du formulaire au premier chargement, 
        // ou de réafficher le formulaire avec les erreurs si la validation a échoué.
        return $this->render('animal/new.html.twig', [
            'animal' => $animal,
            'form' => $form->createView(), // ou '$form' selon votre version de Symfony
        ]);
    }

    #[Route('/{id}', name: 'app_animal_show', methods: ['GET'])]
    public function show(Animal $animal): Response
    {
        return $this->render('animal/show.html.twig', [
            'animal' => $animal,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_animal_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Animal $animal, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AnimalType::class, $animal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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

    #[Route('/{id}', name: 'app_animal_delete', methods: ['POST'])]
    public function delete(Request $request, Animal $animal, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $animal->getId(), $request->getPayload()->getString('_token'))) {
            $this->deletePhysicalDocuments($animal->getDocuments());

            $entityManager->remove($animal);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_animal_index', [], Response::HTTP_SEE_OTHER);
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

        $documents = $this->documentUploader->uploadMany(
            $uploadedFiles,
            $this->animalUploadsDirectory
        );

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