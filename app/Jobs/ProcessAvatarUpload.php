<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessAvatarUpload implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public string $tempFilePath,
        public string $originalName
    ) {}

    public function handle(): void
    {
        Log::debug('ProcessAvatarUpload::handle()', [
            'user_id' => $this->userId,
            'temp_file' => $this->tempFilePath,
            'original_name' => $this->originalName
        ]);

        $user = User::find($this->userId);
        if (! $user) {
            Log::warning('ProcessAvatarUpload: user not found', ['user_id' => $this->userId]);
            $this->cleanupTempFile();
            return;
        }

        try {
            // Validate file exists and is readable
            if (! file_exists($this->tempFilePath) || ! is_readable($this->tempFilePath)) {
                throw new \Exception('Temp file not accessible: ' . $this->tempFilePath);
            }

            // Get image info and validate
            $imageInfo = getimagesize($this->tempFilePath);
            if (! $imageInfo) {
                throw new \Exception('Invalid image file');
            }

            // Validate image dimensions and size
            if ($imageInfo[0] < 50 || $imageInfo[1] < 50) {
                throw new \Exception('Image must be at least 50x50 pixels');
            }

            if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
                throw new \Exception('Image dimensions too large (max 5000x5000)');
            }

            // Generate unique filename
            $filename = 'avatars/' . $user->id . '_' . time() . '.jpg';
            
            // Process the image
            $processedImage = $this->processImage($this->tempFilePath, $imageInfo);
            
            // Save to storage
            Storage::disk('public')->put($filename, $processedImage);
            
            // Remove old avatar if exists
            if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            
            // Update user record
            $user->update([
                'avatar_path' => $filename,
                'use_gravatar' => false
            ]);
            
            Log::info('Avatar processed and saved', [
                'user_id' => $this->userId,
                'avatar_path' => $filename,
                'original_size' => $imageInfo[0] . 'x' . $imageInfo[1],
                'processed_size' => '200x200'
            ]);
            
        } catch (\Throwable $e) {
            Log::error('ProcessAvatarUpload: failed to process avatar', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            $this->cleanupTempFile();
        }
    }

    private function processImage(string $sourcePath, array $imageInfo): string
    {
        $targetSize = 200;
        
        // Create image resource based on type
        $sourceImage = match($imageInfo[2]) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => imagecreatefrompng($sourcePath),
            IMAGETYPE_GIF => imagecreatefromgif($sourcePath),
            IMAGETYPE_WEBP => imagecreatefromwebp($sourcePath),
            default => throw new \Exception('Unsupported image type')
        };

        if (! $sourceImage) {
            throw new \Exception('Failed to create image resource');
        }

        // Create target image
        $targetImage = imagecreatetruecolor($targetSize, $targetSize);
        
        // Handle transparency for PNG and GIF
        if ($imageInfo[2] === IMAGETYPE_PNG || $imageInfo[2] === IMAGETYPE_GIF) {
            imagealphablending($targetImage, false);
            imagesavealpha($targetImage, true);
            $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
            imagefill($targetImage, 0, 0, $transparent);
        } else {
            // Fill with white background for JPEG
            $white = imagecolorallocate($targetImage, 255, 255, 255);
            imagefill($targetImage, 0, 0, $white);
        }

        // Calculate crop dimensions for square aspect ratio
        $sourceWidth = $imageInfo[0];
        $sourceHeight = $imageInfo[1];
        $cropSize = min($sourceWidth, $sourceHeight);
        $cropX = ($sourceWidth - $cropSize) / 2;
        $cropY = ($sourceHeight - $cropSize) / 2;

        // Resize and crop to square
        imagecopyresampled(
            $targetImage, $sourceImage,
            0, 0, $cropX, $cropY,
            $targetSize, $targetSize, $cropSize, $cropSize
        );

        // Generate JPEG output
        ob_start();
        imagejpeg($targetImage, null, 85); // 85% quality
        $imageData = ob_get_contents();
        ob_end_clean();

        // Clean up resources
        imagedestroy($sourceImage);
        imagedestroy($targetImage);

        if (! $imageData) {
            throw new \Exception('Failed to generate processed image');
        }

        return $imageData;
    }

    private function cleanupTempFile(): void
    {
        if (file_exists($this->tempFilePath)) {
            unlink($this->tempFilePath);
            Log::debug('Cleaned up temp file', ['path' => $this->tempFilePath]);
        }
    }
}
