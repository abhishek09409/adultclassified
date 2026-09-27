<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use RuntimeException;

final class ImageService
{
    private const MAX_BYTES = 5242880;

    /** @var array<string, string> */
    private const MIME_EXTENSIONS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * @param array<string, mixed> $file
     */
    public function storeForListing(int $listingId, array $file, string $alt): ?string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            return 'The image could not be uploaded.';
        }

        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return 'The upload was not accepted.';
        }
        if ($size < 1 || $size > self::MAX_BYTES) {
            return 'Images must be smaller than 5 MB.';
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        if (!is_string($mime) || !isset(self::MIME_EXTENSIONS[$mime])) {
            return 'Only JPEG, PNG, and WebP images are accepted.';
        }

        $info = getimagesize($tmp);
        if ($info === false) {
            return 'The file is not a valid image.';
        }

        $extension = self::MIME_EXTENSIONS[$mime];
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $directory = BASE_PATH . '/public/uploads/listings';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return 'The upload folder is not available.';
        }

        $target = $directory . '/' . $filename;
        if (!move_uploaded_file($tmp, $target)) {
            return 'The image could not be saved.';
        }

        $thumbName = $this->thumbnail($target, $extension);
        $this->insertImage($listingId, $filename, 'listings/' . $filename, $thumbName, $alt, (int) $info[0], (int) $info[1], $size, $mime);

        return null;
    }

    public function ensureLibrary(): void
    {
        $count = (int) Database::connection()->query("SELECT COUNT(*) FROM media_assets WHERE status = 'active'")->fetchColumn();
        if ($count > 0) {
            return;
        }

        $directory = BASE_PATH . '/public/uploads/media';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $palettes = [
            [[90, 24, 46], [244, 214, 196]],
            [[28, 43, 74], [186, 206, 214]],
            [[47, 62, 48], [214, 206, 176]],
            [[72, 40, 84], [220, 198, 214]],
            [[18, 62, 68], [176, 214, 206]],
            [[92, 54, 28], [232, 206, 170]],
            [[36, 36, 48], [206, 198, 214]],
            [[64, 24, 36], [214, 176, 168]],
        ];
        $statement = Database::connection()->prepare(
            'INSERT INTO media_assets (filename, path, alt_text, status) VALUES (:filename, :path, :alt_text, :status)'
        );

        for ($i = 0; $i < 16; $i++) {
            $palette = $palettes[$i % count($palettes)];
            $shift = ($i * 17) % 40;
            $colors = [
                [min(255, $palette[0][0] + $shift), $palette[0][1], $palette[0][2]],
                [$palette[1][0], min(255, $palette[1][1] - $shift), $palette[1][2]],
            ];
            $filename = 'cover-' . str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) . '.jpg';
            $this->drawCover($directory . '/' . $filename, $colors, $i + 1);
            $statement->execute([
                'filename' => $filename,
                'path' => 'media/' . $filename,
                'alt_text' => 'Abstract directory cover ' . ($i + 1),
                'status' => 'active',
            ]);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function nextAsset(array $excludeIds): ?array
    {
        $sql = "SELECT id, filename, path, alt_text FROM media_assets WHERE status = 'active'";
        $params = [];
        if ($excludeIds !== []) {
            $placeholders = [];
            foreach (array_values($excludeIds) as $index => $id) {
                $key = 'id' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }
            $sql .= ' AND id NOT IN (' . implode(',', $placeholders) . ')';
        }
        $sql .= ' ORDER BY last_used_at IS NULL DESC, last_used_at ASC, usage_count ASC, id ASC LIMIT 1';
        $statement = Database::connection()->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /**
     * @param array<string, mixed> $asset
     */
    public function attachAsset(int $listingId, array $asset): void
    {
        $source = BASE_PATH . '/public/uploads/' . $asset['path'];
        if (!is_file($source)) {
            throw new RuntimeException('Image asset is missing.');
        }
        $filename = bin2hex(random_bytes(16)) . '.jpg';
        $target = BASE_PATH . '/public/uploads/listings/' . $filename;
        if (!copy($source, $target)) {
            throw new RuntimeException('Image asset could not be copied.');
        }
        $thumb = $this->thumbnail($target, 'jpg');
        $size = (int) filesize($target);
        $this->insertImage($listingId, $filename, 'listings/' . $filename, $thumb, (string) $asset['alt_text'], 1200, 800, $size, 'image/jpeg');
        Database::connection()->prepare(
            'UPDATE media_assets SET usage_count = usage_count + 1, last_used_at = NOW() WHERE id = :id'
        )->execute(['id' => (int) $asset['id']]);
    }

    public function deleteFile(?string $relativePath): void
    {
        if ($relativePath === null || $relativePath === '' || str_contains($relativePath, '..')) {
            return;
        }
        $root = realpath(BASE_PATH . '/public/uploads');
        $full = realpath(BASE_PATH . '/public/uploads/' . ltrim($relativePath, '/'));
        if ($root === false || $full === false || !str_starts_with($full, $root . DIRECTORY_SEPARATOR)) {
            return;
        }
        if (is_file($full)) {
            unlink($full);
        }
    }

    /**
     * @param array{0: array{0: int, 1: int, 2: int}, 1: array{0: int, 1: int, 2: int}} $colors
     */
    private function drawCover(string $path, array $colors, int $number): void
    {
        $image = imagecreatetruecolor(1200, 800);
        if ($image === false) {
            throw new RuntimeException('GD could not create an image.');
        }
        $start = $colors[0];
        $end = $colors[1];
        for ($y = 0; $y < 800; $y++) {
            $ratio = $y / 800;
            $color = imagecolorallocate(
                $image,
                (int) ($start[0] + ($end[0] - $start[0]) * $ratio),
                (int) ($start[1] + ($end[1] - $start[1]) * $ratio),
                (int) ($start[2] + ($end[2] - $start[2]) * $ratio)
            );
            imageline($image, 0, $y, 1200, $y, $color);
        }
        $ink = imagecolorallocate($image, 255, 250, 245);
        imagestring($image, 5, 80, 360, 'DIRECTORY  21+', $ink);
        imagestring($image, 5, 80, 400, 'COVER ' . $number, $ink);
        imagejpeg($image, $path, 85);
        imagedestroy($image);
    }

    private function thumbnail(string $sourcePath, string $extension): ?string
    {
        $source = match ($extension) {
            'jpg' => imagecreatefromjpeg($sourcePath),
            'png' => imagecreatefrompng($sourcePath),
            'webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($sourcePath) : false,
            default => false,
        };
        if ($source === false) {
            return null;
        }
        $width = imagesx($source);
        $height = imagesy($source);
        $targetWidth = 640;
        $targetHeight = max(1, (int) round($height * ($targetWidth / max(1, $width))));
        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($thumb === false) {
            imagedestroy($source);
            return null;
        }
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);
        $name = 'thumb-' . pathinfo($sourcePath, PATHINFO_FILENAME) . '.jpg';
        imagejpeg($thumb, dirname($sourcePath) . '/' . $name, 82);
        imagedestroy($source);
        imagedestroy($thumb);

        return 'listings/' . $name;
    }

    private function insertImage(int $listingId, string $filename, string $path, ?string $thumb, string $alt, int $width, int $height, int $size, string $mime): void
    {
        $sort = Database::connection()->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM listing_images WHERE listing_id = :id');
        $sort->execute(['id' => $listingId]);
        $statement = Database::connection()->prepare(
            'INSERT INTO listing_images (listing_id, filename, path, thumbnail_path, alt_text, mime_type, file_size, width, height, sort_order)
             VALUES (:listing_id, :filename, :path, :thumbnail_path, :alt_text, :mime_type, :file_size, :width, :height, :sort_order)'
        );
        $statement->execute([
            'listing_id' => $listingId,
            'filename' => $filename,
            'path' => $path,
            'thumbnail_path' => $thumb,
            'alt_text' => mb_substr($alt, 0, 180),
            'mime_type' => $mime,
            'file_size' => $size,
            'width' => $width,
            'height' => $height,
            'sort_order' => (int) $sort->fetchColumn(),
        ]);
    }
}
