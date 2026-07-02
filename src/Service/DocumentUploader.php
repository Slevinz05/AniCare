<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DocumentUploader
{
    private string $targetDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    public function uploadMany(array $files): array
    {
        $documents = [];

        /** @var UploadedFile $file */
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            // 1. EXTRAIRE LES INFOS DU FICHIER TANT QU'IL EST DANS LE DOSSIER TEMPORAIRE
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
            
            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $size = $file->getSize(); // <-- On récupère la taille ICI, avant le move !

            // 2. DÉPLACER LE FICHIER VERS SA DESTINATION FINALE
            try {
                $file->move($this->targetDirectory, $newFileName);
            } catch (FileException $e) {
                // Gérer l'exception si le déplacement échoue
                continue;
            }

            // 3. ENREGISTRER LES DONNÉES EN MÉMOIRE (La variable $size contient la valeur)
            $documents[] = [
                'fileName' => $newFileName,
                'originalName' => $originalName,
                'mimeType' => $mimeType,
                'size' => $size, // <-- Utilisation de la variable stockée en amont
                'uploadedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ];
        }

        return $documents;
    }
}