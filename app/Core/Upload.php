<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class Upload
{
    /** @var array<string, string> */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /**
     * Store an uploaded image under public/{dir} and return its assets-relative path.
     */
    public static function store(array $file, string $dir): string
    {
        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if (is_int($error) && $error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::errorMessage($error));
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        $maxBytes = (int) config('app.uploads.max_size', 5 * 1024 * 1024);

        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new RuntimeException('The uploaded file could not be read.');
        }

        if ($size <= 0 || $size > $maxBytes) {
            throw new RuntimeException('The image must be smaller than ' . self::formatBytes($maxBytes) . '.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        if ($finfo === false) {
            throw new RuntimeException('Unable to validate the uploaded file.');
        }

        $mime = finfo_file($finfo, $tmpPath);
        finfo_close($finfo);

        if (!is_string($mime) || !isset(self::MIME_EXTENSIONS[$mime])) {
            throw new RuntimeException('Only JPEG, PNG, WebP, and GIF images are allowed.');
        }

        if (@getimagesize($tmpPath) === false) {
            throw new RuntimeException('The uploaded file is not a valid image.');
        }

        $relativeDir = self::normalizeDir($dir);
        $targetDir = base_path('public/' . $relativeDir);
        $extension = self::MIME_EXTENSIONS[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Unable to create the upload directory.');
        }

        if (!move_uploaded_file($tmpPath, $targetDir . DIRECTORY_SEPARATOR . $filename)) {
            throw new RuntimeException('Unable to save the uploaded file.');
        }

        $storedPath = $relativeDir . '/' . $filename;

        if (str_starts_with($storedPath, 'assets/')) {
            $storedPath = substr($storedPath, 7);
        }

        return $storedPath;
    }

    private static function normalizeDir(string $dir): string
    {
        $dir = trim(str_replace('\\', '/', $dir), '/');

        if ($dir === '' || preg_match('#(^|/)\.\.(/|$)#', $dir) === 1) {
            throw new RuntimeException('Invalid upload directory.');
        }

        return $dir;
    }

    private static function errorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded file is too large.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'The upload temporary directory is missing.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write the uploaded file to disk.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
            default => 'The upload failed.',
        };
    }

    private static function formatBytes(int $bytes): string
    {
        return $bytes >= 1024 * 1024
            ? round($bytes / (1024 * 1024), 1) . ' MB'
            : round($bytes / 1024) . ' KB';
    }
}
