<?php

namespace App\Controller;

use App\Entity\HealthBookEntry;
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

#[Route('/health/book/entry')]
final class HealthBookEntryController extends AbstractController
{
    public function __construct(
        private readonly DocumentUploader $documentUploader,

        #[Autowire('%kernel.project_dir%/public/uploads/health-book-entries')]
        private readonly string $healthBookEntryUploadsDirectory,
    ) {
    }

    #[Route(name: 'app_health_book_entry_index', methods: ['GET'])]
    public function index(HealthBookEntryRepository $healthBookEntryRepository): Response
    {
        return $this->render('health_book_entry/index.html.twig', [
            'health_book_entries' => $healthBookEntryRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_health_book_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $healthBookEntry = new HealthBookEntry();

        $presetDateString = $request->query->get('preset_date');

        if ($presetDateString) {
            try {
                $presetDate = new \DateTimeImmutable($presetDateString);
                $healthBookEntry->setDate($presetDate);
            } catch (\Exception) {
                // Date invalide : on laisse le champ vide.
            }
        }

        $form = $this->createForm(HealthBookEntryType::class, $healthBookEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        return $this->render('health_book_entry/show.html.twig', [
            'health_book_entry' => $healthBookEntry,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_health_book_entry_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, HealthBookEntry $healthBookEntry, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(HealthBookEntryType::class, $healthBookEntry);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        if ($this->isCsrfTokenValid('delete' . $healthBookEntry->getId(), $request->getPayload()->getString('_token'))) {
            $this->deletePhysicalDocuments($healthBookEntry->getDocuments());

            $entityManager->remove($healthBookEntry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_health_book_entry_index', [], Response::HTTP_SEE_OTHER);
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

        $documents = $this->documentUploader->uploadMany(
            $uploadedFiles,
            $this->healthBookEntryUploadsDirectory
        );

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