<?php

namespace App\Controller;

use App\Entity\HealthBookEntry;
use App\Entity\User;
use App\Form\HealthBookEntryType;
use App\Repository\HealthBookEntryRepository;
use App\Service\DocumentUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/health/book/entry')]
#[IsGranted('ROLE_USER')]
final class HealthBookEntryController extends AbstractController
{
    public function __construct(
        private readonly DocumentUploader $documentUploader,

        #[Autowire('%kernel.project_dir%/var/uploads/health-book-entries')]
        private readonly string $healthBookEntryUploadsDirectory,
    ) {
    }

    #[Route(name: 'app_health_book_entry_index', methods: ['GET'])]
    public function index(HealthBookEntryRepository $healthBookEntryRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('health_book_entry/index.html.twig', [
            'health_book_entries' => $healthBookEntryRepository->findAccessibleByUser($user),
        ]);
    }

    #[Route('/new', name: 'app_health_book_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $healthBookEntry = new HealthBookEntry();

        $presetDateString = $request->query->get('preset_date');

        if ($presetDateString) {
            try {
                $presetDate = new \DateTimeImmutable($presetDateString);
                $healthBookEntry->setDate($presetDate);
            } catch (\Exception) {
            }
        }

        $form = $this->createForm(HealthBookEntryType::class, $healthBookEntry, [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncTypeCustom($form, $healthBookEntry);
            $this->handleUploadedDocuments($form, $healthBookEntry);

            $entityManager->persist($healthBookEntry);
            $entityManager->flush();

            return $this->redirectToRoute('app_health_book_entry_show', [
                'id' => $healthBookEntry->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('health_book_entry/new.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_health_book_entry_show', methods: ['GET'])]
    public function show(HealthBookEntry $healthBookEntry): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $healthBookEntry->getAnimal());

        return $this->render('health_book_entry/show.html.twig', [
            'health_book_entry' => $healthBookEntry,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_health_book_entry_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $healthBookEntry->getAnimal());

        /** @var User $user */
        $user = $this->getUser();

        $form = $this->createForm(HealthBookEntryType::class, $healthBookEntry, [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncTypeCustom($form, $healthBookEntry);
            $this->handleUploadedDocuments($form, $healthBookEntry);

            $entityManager->flush();

            return $this->redirectToRoute('app_health_book_entry_show', [
                'id' => $healthBookEntry->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('health_book_entry/edit.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_health_book_entry_delete', methods: ['POST'])]
    public function delete(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_DELETE', $healthBookEntry->getAnimal());

        if ($this->isCsrfTokenValid('delete' . $healthBookEntry->getId(), $request->getPayload()->getString('_token'))) {
            $this->deletePhysicalDocuments($healthBookEntry->getDocuments());

            $entityManager->remove($healthBookEntry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_health_book_entry_index', [], Response::HTTP_SEE_OTHER);
    }

    private function syncTypeCustom(FormInterface $form, HealthBookEntry $healthBookEntry): void
    {
        if ($healthBookEntry->getType() === 'Autre') {
            $custom = $form->get('typeCustom')->getData();
            if ($custom) {
                $healthBookEntry->setType($custom);
            }
        }
    }

    private function handleUploadedDocuments(FormInterface $form, HealthBookEntry $healthBookEntry): void
    {
        if (!$form->has('attachments')) {
            return;
        }

        $uploadedFiles = $form->get('attachments')->getData();

        if (empty($uploadedFiles)) {
            return;
        }

        $documents = $this->documentUploader->uploadMany($uploadedFiles, $this->healthBookEntryUploadsDirectory);

        foreach ($documents as $document) {
            $healthBookEntry->addDocument($document);
        }
    }

    private function deletePhysicalDocuments(array $documents): void
    {
        foreach ($documents as $document) {
            if (!isset($document['fileName'])) {
                continue;
            }

            $filePath = $this->healthBookEntryUploadsDirectory . '/' . $document['fileName'];

            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
    }
}
