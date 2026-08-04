<?php

namespace App\Controller;

use App\Entity\HealthBookEntry;
use App\Entity\User;
use App\Form\HealthBookEntryType;
use App\Repository\AnimalRepository;
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

#[Route('/consultations')]
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

    #[Route('/importer', name: 'app_health_book_entry_import', methods: ['GET', 'POST'])]
    public function import(
        Request $request,
        EntityManagerInterface $entityManager,
        AnimalRepository $animalRepository,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $animals = $animalRepository->findAccessibleAnimals($user);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('import_documents', $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_health_book_entry_import');
            }

            $mode = $request->request->get('mode', 'existing');
            $animal = null;

            if ($mode === 'new') {
                $newName = trim($request->request->get('new_animal_name', ''));
                if (!$newName) {
                    $this->addFlash('danger', 'Veuillez saisir le nom du cheval.');
                    return $this->redirectToRoute('app_health_book_entry_import');
                }

                $animal = new \App\Entity\Animal();
                $animal->setName($newName);
                $animal->setOwner($user);
                $animal->setSpecies('Cheval');
                $animal->setGender($request->request->get('new_animal_gender', 'Hongre'));
                $entityManager->persist($animal);
                $entityManager->flush();
                $animal->ensureSlug();
                $entityManager->flush();
            } else {
                $animalId = $request->request->get('animal_id');
                if ($animalId) {
                    $animal = $animalRepository->find((int) $animalId);
                }
            }

            if (!$animal) {
                $this->addFlash('danger', 'Veuillez sélectionner ou créer un cheval.');
                return $this->redirectToRoute('app_health_book_entry_import');
            }

            $uploadedFiles = $request->files->all('documents');
            if (empty($uploadedFiles)) {
                $this->addFlash('danger', 'Veuillez sélectionner au moins un document.');
                return $this->redirectToRoute('app_health_book_entry_import');
            }

            $title = trim($request->request->get('title', '')) ?: 'Import de documents';

            $entry = new HealthBookEntry();
            $entry->setTitle($title);
            $entry->setType('Autre');
            $entry->setDate(new \DateTimeImmutable());
            $entry->setAnimal($animal);
            $entry->setDescription('Documents importés le' . date('d/m/Y'));

            $documents = $this->documentUploader->uploadMany($uploadedFiles, $this->healthBookEntryUploadsDirectory);
            foreach ($documents as $document) {
                $entry->addDocument($document);
            }

            $entityManager->persist($entry);
            $entityManager->flush();

            $this->addFlash('success', count($documents) . ' document(s) importé(s) avec succès.');

            return $this->redirectToRoute('app_health_book_entry_show', ['id' => $entry->getId()]);
        }

        return $this->render('health_book_entry/import.html.twig', [
            'animals' => $animals,
        ]);
    }

    #[Route('/nouvelle', name: 'app_health_book_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AnimalRepository $animalRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $healthBookEntry = new HealthBookEntry();

        $presetDateString = $request->query->get('preset_date');
        if ($presetDateString) {
            try {
                $healthBookEntry->setDate(new \DateTimeImmutable($presetDateString));
            } catch (\Exception) {
            }
        }

        $animalId = $request->query->get('animal');
        if ($animalId) {
            $animal = $animalRepository->find((int) $animalId);
            if ($animal) {
                $healthBookEntry->setAnimal($animal);
            }
        }

        if ($this->isGranted('ROLE_PRO')) {
            $healthBookEntry->setVeterinarian($user);
        }

        $form = $this->createForm(HealthBookEntryType::class, $healthBookEntry, [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->syncTypeCustom($form, $healthBookEntry);
            $this->handleUploadedDocuments($form, $healthBookEntry);

            $isDraft = $request->request->get('save_draft') !== null;
            $healthBookEntry->setStatus($isDraft ? 'draft' : 'published');

            $entityManager->persist($healthBookEntry);
            $entityManager->flush();

            if ($isDraft) {
                $this->addFlash('success', 'Brouillon enregistré.');
                return $this->redirectToRoute('app_health_book_entry_edit', [
                    'id' => $healthBookEntry->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_health_book_entry_show', [
                'id' => $healthBookEntry->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('health_book_entry/new.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/details', name: 'app_health_book_entry_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(HealthBookEntry $healthBookEntry): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $healthBookEntry->getAnimal());

        return $this->render('health_book_entry/show.html.twig', [
            'health_book_entry' => $healthBookEntry,
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_health_book_entry_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
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

            $isDraft = $request->request->get('save_draft') !== null;
            $healthBookEntry->setStatus($isDraft ? 'draft' : 'published');

            $entityManager->flush();

            if ($isDraft) {
                $this->addFlash('success', 'Brouillon enregistré.');
                return $this->redirectToRoute('app_health_book_entry_edit', [
                    'id' => $healthBookEntry->getId(),
                ], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_health_book_entry_show', [
                'id' => $healthBookEntry->getId(),
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('health_book_entry/edit.html.twig', [
            'health_book_entry' => $healthBookEntry,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/document/{fileName}/supprimer', name: 'app_health_book_entry_document_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function deleteDocument(Request $request, HealthBookEntry $healthBookEntry, string $fileName, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ANIMAL_EDIT', $healthBookEntry->getAnimal());

        if ($this->isCsrfTokenValid('delete_doc' . $fileName, $request->request->get('_token'))) {
            $filePath = $this->healthBookEntryUploadsDirectory . '/' . $fileName;
            if (is_file($filePath)) {
                unlink($filePath);
            }

            $healthBookEntry->removeDocument($fileName);
            $entityManager->flush();

            $this->addFlash('success', 'Document supprime.');
        }

        return $this->redirectToRoute('app_health_book_entry_show', ['id' => $healthBookEntry->getId()]);
    }

    #[Route('/{id}/supprimer', name: 'app_health_book_entry_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
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
