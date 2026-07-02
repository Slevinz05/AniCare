<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class DocumentUploader
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function uploadMany(array $files, string $targetDirectory): array
    {
        $documents = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $this->slugger->slug($originalFilename);
            $newFileName = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();

            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType();
            $size = $file->getSize();

            try {
                $file->move($targetDirectory, $newFileName);
            } catch (FileException) {
                continue;
            }

            $documents[] = [
                'fileName' => $newFileName,
                'originalName' => $originalName,
                'mimeType' => $mimeType,
                'size' => $size,
                'uploadedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ];
        }

        return $documents;
    }
}
