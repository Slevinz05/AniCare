<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class DocumentUploader
{
    public function __construct(
        private readonly SluggerInterface $slugger
    ) {
    }

    /**
     * @param UploadedFile[] $files
     */
    public function uploadMany(array $files, string $targetDirectory): array
    {
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0775, true);
        }

        $documents = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeName = strtolower($this->slugger->slug($originalName));
            $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();

            $newFileName = $safeName . '-' . uniqid('', true) . '.' . $extension;

            $file->move($targetDirectory, $newFileName);

            $documents[] = [
                'fileName' => $newFileName,
                'originalName' => $file->getClientOriginalName(),
                'mimeType' => $file->getClientMimeType(),
                'size' => $file->getSize(),
                'uploadedAt' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ];
        }

        return $documents;
    }
}