<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\AnimalRepository;
use App\Repository\HealthBookEntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class DocumentController extends AbstractController
{
    #[Route('/download/animal/{animalId}/{fileName}', name: 'app_document_download_animal', methods: ['GET'])]
    public function downloadAnimalDocument(
        int $animalId,
        string $fileName,
        AnimalRepository $animalRepository,
        #[Autowire('%kernel.project_dir%/var/uploads/animals')] string $uploadsDir,
    ): BinaryFileResponse {
        $animal = $animalRepository->find($animalId);

        if (!$animal) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $animal);

        return $this->serveFile($uploadsDir, $fileName, $animal->getDocuments());
    }

    #[Route('/download/health-entry/{entryId}/{fileName}', name: 'app_document_download_health_entry', methods: ['GET'])]
    public function downloadHealthEntryDocument(
        int $entryId,
        string $fileName,
        HealthBookEntryRepository $entryRepository,
        #[Autowire('%kernel.project_dir%/var/uploads/health-book-entries')] string $uploadsDir,
    ): BinaryFileResponse {
        $entry = $entryRepository->find($entryId);

        if (!$entry) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('ANIMAL_VIEW', $entry->getAnimal());

        return $this->serveFile($uploadsDir, $fileName, $entry->getDocuments());
    }

    private function serveFile(string $uploadsDir, string $fileName, array $documents): BinaryFileResponse
    {
        $found = false;
        $originalName = $fileName;

        foreach ($documents as $document) {
            if (($document['fileName'] ?? null) === $fileName) {
                $found = true;
                $originalName = $document['originalName'] ?? $fileName;
                break;
            }
        }

        if (!$found) {
            throw $this->createNotFoundException();
        }

        $filePath = $uploadsDir . '/' . basename($fileName);

        if (!is_file($filePath)) {
            throw $this->createNotFoundException();
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $originalName);

        return $response;
    }
}
