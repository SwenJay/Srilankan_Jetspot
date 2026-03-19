<?php
// ============================================================
//  SL JetSpot — Image Helper
//  Auto-compresses uploads and generates thumbnails via GD
// ============================================================

class ImageHelper {

    // Max dimensions for the gallery thumbnail (shown in cards)
    const THUMB_W = 800;
    const THUMB_H = 600;

    // Max dimensions for the full image (shown in lightbox)
    const FULL_W  = 1920;
    const FULL_H  = 1440;

    // JPEG quality (0-100). 82 is visually lossless for web.
    const QUALITY = 82;

    /**
     * Process an uploaded image:
     *  - Resizes + compresses the original to FULL dimensions → uploads/full/
     *  - Creates a small thumbnail                            → uploads/thumbs/
     *
     * Returns ['full' => 'uploads/full/xxx.jpg', 'thumb' => 'uploads/thumbs/xxx.jpg']
     * or throws RuntimeException on failure.
     */
    public static function process(string $tmpPath, string $originalName): array {
        if (!extension_loaded('gd')) {
            throw new RuntimeException('GD extension is not available on this server.');
        }

        $mime = mime_content_type($tmpPath);
        $src  = self::createSource($tmpPath, $mime);
        if (!$src) {
            throw new RuntimeException('Could not read image. Make sure it is a valid JPEG, PNG, or WebP.');
        }

        $origW = imagesx($src);
        $origH = imagesy($src);

        // Build a unique base filename (no extension — we always save as JPEG)
        $base = 'aircraft_' . time() . '_' . bin2hex(random_bytes(4));

        // Ensure output directories exist
        self::ensureDir(UPLOAD_DIR . 'full/');
        self::ensureDir(UPLOAD_DIR . 'thumbs/');

        // --- Full image (for lightbox) ---
        [$fullW, $fullH] = self::fitDimensions($origW, $origH, self::FULL_W, self::FULL_H);
        $fullImg  = self::resample($src, $origW, $origH, $fullW, $fullH);
        $fullFile = UPLOAD_DIR . 'full/' . $base . '.jpg';
        if (!imagejpeg($fullImg, $fullFile, self::QUALITY)) {
            throw new RuntimeException('Failed to write full image to disk.');
        }
        imagedestroy($fullImg);

        // --- Thumbnail (for gallery cards) ---
        [$thumbW, $thumbH] = self::fitDimensions($origW, $origH, self::THUMB_W, self::THUMB_H);
        $thumbImg  = self::resample($src, $origW, $origH, $thumbW, $thumbH);
        $thumbFile = UPLOAD_DIR . 'thumbs/' . $base . '.jpg';
        if (!imagejpeg($thumbImg, $thumbFile, self::QUALITY)) {
            throw new RuntimeException('Failed to write thumbnail to disk.');
        }
        imagedestroy($thumbImg);

        imagedestroy($src);

        return [
            'full'  => 'uploads/full/'   . $base . '.jpg',
            'thumb' => 'uploads/thumbs/' . $base . '.jpg',
        ];
    }

    /**
     * Delete both full and thumb versions of an image path.
     * Safe — only deletes files inside the uploads/ directory.
     */
    public static function delete(string $imagePath, string $thumbPath = ''): void {
        foreach ([$imagePath, $thumbPath] as $path) {
            if (!$path) continue;
            // Only delete files we own (inside uploads/)
            if (strpos($path, 'uploads/') === 0) {
                $full = UPLOAD_DIR . ltrim(str_replace('uploads/', '', $path), '/');
                // Handle both old flat structure and new full/thumbs subdirs
                $alt  = rtrim(UPLOAD_DIR, '/') . '/' . ltrim(str_replace('uploads/', '', $path), '/');
                foreach ([$full, $alt] as $f) {
                    if (file_exists($f)) @unlink($f);
                }
            }
        }
    }

    // ---- Private helpers ----

    private static function createSource(string $path, string $mime) {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png'  => imagecreatefrompng($path),
            'image/webp' => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($path) : false,
            default      => false,
        };
    }

    private static function fitDimensions(int $srcW, int $srcH, int $maxW, int $maxH): array {
        if ($srcW <= $maxW && $srcH <= $maxH) {
            return [$srcW, $srcH]; // already small enough, no upscaling
        }
        $ratio  = min($maxW / $srcW, $maxH / $srcH);
        return [(int) round($srcW * $ratio), (int) round($srcH * $ratio)];
    }

    private static function resample($src, int $srcW, int $srcH, int $dstW, int $dstH) {
        $dst = imagecreatetruecolor($dstW, $dstH);
        // Preserve transparency for PNG sources
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 255, 255, 255, 127);
        imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $transparent);
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        return $dst;
    }

    private static function ensureDir(string $dir): void {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}