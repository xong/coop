<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    public function __construct(
        private string $uploadDir,
        private SluggerInterface $slugger,
    ) {}

    public function upload(UploadedFile $file, string $subDir = ''): array
    {
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalName);
        $extension = $file->guessExtension() ?? $file->getClientOriginalExtension();
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $extension;

        $targetDir = rtrim($this->uploadDir, '/') . ($subDir ? '/' . ltrim($subDir, '/') : '');
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $file->move($targetDir, $newFilename);

        $relativePath = 'uploads/files' . ($subDir ? '/' . ltrim($subDir, '/') : '') . '/' . $newFilename;

        return [
            'originalName' => $file->getClientOriginalName(),
            'storagePath' => $relativePath,
            'mimeType' => $file->getMimeType() ?? 'application/octet-stream',
            'fileSize' => filesize($targetDir . '/' . $newFilename),
        ];
    }

    public function delete(string $storagePath, string $publicDir): void
    {
        $fullPath = rtrim($publicDir, '/') . '/' . ltrim($storagePath, '/');
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
    }
}
