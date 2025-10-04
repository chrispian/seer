<?php

namespace App\Services;

use App\Jobs\ProcessAvatarUpload;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AvatarService
{
    /**
     * Generate Gravatar URL for email
     */
    public function getGravatarUrl(string $email, int $size = 200, string $default = 'mp'): string
    {
        $hash = md5(strtolower(trim($email)));
        return "https://www.gravatar.com/avatar/{$hash}?s={$size}&d={$default}&r=g";
    }

    /**
     * Check if Gravatar exists for email
     */
    public function hasGravatar(string $email): bool
    {
        $cacheKey = "gravatar_exists:" . md5($email);
        
        return Cache::remember($cacheKey, now()->addHours(24), function () use ($email) {
            $url = $this->getGravatarUrl($email, 80, '404');
            
            try {
                $response = Http::timeout(10)->head($url);
                return $response->successful();
            } catch (\Exception $e) {
                Log::warning('Failed to check Gravatar existence', [
                    'email' => $email,
                    'error' => $e->getMessage()
                ]);
                return false;
            }
        });
    }

    /**
     * Cache Gravatar image locally for offline access
     */
    public function cacheGravatar(User $user, int $size = 200): ?string
    {
        if (! $user->email) {
            return null;
        }

        $hash = md5(strtolower(trim($user->email)));
        $filename = "gravatar_cache/{$hash}_{$size}.jpg";
        
        // Check if already cached and recent
        if (Storage::disk('public')->exists($filename)) {
            $lastModified = Storage::disk('public')->lastModified($filename);
            if ($lastModified && $lastModified > now()->subDays(7)->timestamp) {
                return $filename; // Cache is fresh
            }
        }

        try {
            $url = $this->getGravatarUrl($user->email, $size);
            $response = Http::timeout(30)->get($url);
            
            if ($response->successful()) {
                Storage::disk('public')->put($filename, $response->body());
                
                Log::info('Gravatar cached locally', [
                    'user_id' => $user->id,
                    'filename' => $filename,
                    'size' => $size
                ]);
                
                return $filename;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to cache Gravatar', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
        }

        return null;
    }

    /**
     * Process avatar upload
     */
    public function processUpload(User $user, UploadedFile $file): bool
    {
        try {
            // Validate file
            $this->validateUpload($file);
            
            // Store temporarily
            $tempPath = $file->store('temp', 'local');
            $fullTempPath = storage_path('app/' . $tempPath);
            
            // Dispatch processing job
            ProcessAvatarUpload::dispatch(
                $user->id,
                $fullTempPath,
                $file->getClientOriginalName()
            );
            
            Log::info('Avatar upload queued for processing', [
                'user_id' => $user->id,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize()
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Avatar upload failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Validate uploaded file
     */
    private function validateUpload(UploadedFile $file): void
    {
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedMimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        
        if (! in_array($file->getMimeType(), $allowedMimes)) {
            throw new \InvalidArgumentException('Invalid file type. Please upload JPEG, PNG, GIF, or WebP.');
        }
        
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException('File too large. Maximum size is 5MB.');
        }
        
        // Validate image dimensions
        $imageInfo = getimagesize($file->getPathname());
        if (! $imageInfo) {
            throw new \InvalidArgumentException('Invalid image file.');
        }
        
        if ($imageInfo[0] < 50 || $imageInfo[1] < 50) {
            throw new \InvalidArgumentException('Image too small. Minimum size is 50x50 pixels.');
        }
        
        if ($imageInfo[0] > 5000 || $imageInfo[1] > 5000) {
            throw new \InvalidArgumentException('Image too large. Maximum size is 5000x5000 pixels.');
        }
    }

    /**
     * Get user's current avatar URL
     */
    public function getAvatarUrl(User $user, int $size = 200): string
    {
        // Custom avatar takes precedence
        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            return Storage::disk('public')->url($user->avatar_path);
        }
        
        // Gravatar if enabled and email exists
        if ($user->use_gravatar && $user->email) {
            // Try to use cached version first
            $cachedPath = $this->cacheGravatar($user, $size);
            if ($cachedPath && Storage::disk('public')->exists($cachedPath)) {
                return Storage::disk('public')->url($cachedPath);
            }
            
            // Fall back to direct Gravatar URL
            return $this->getGravatarUrl($user->email, $size);
        }
        
        // Default avatar
        return asset('interface/avatars/default-avatar.svg');
    }

    /**
     * Remove user's custom avatar
     */
    public function removeCustomAvatar(User $user): void
    {
        if ($user->avatar_path && Storage::disk('public')->exists($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
            
            $user->update([
                'avatar_path' => null,
                'use_gravatar' => true
            ]);
            
            Log::info('Custom avatar removed', ['user_id' => $user->id]);
        }
    }

    /**
     * Clean up old avatar files
     */
    public function cleanupOldAvatars(int $daysOld = 30): int
    {
        $cleaned = 0;
        $cutoff = now()->subDays($daysOld);
        
        try {
            $files = Storage::disk('public')->files('avatars');
            
            foreach ($files as $file) {
                $lastModified = Storage::disk('public')->lastModified($file);
                if ($lastModified && $lastModified < $cutoff->timestamp) {
                    // Check if file is still referenced by any user
                    $inUse = User::where('avatar_path', $file)->exists();
                    
                    if (! $inUse) {
                        Storage::disk('public')->delete($file);
                        $cleaned++;
                    }
                }
            }
            
            Log::info('Avatar cleanup completed', [
                'files_cleaned' => $cleaned,
                'cutoff_date' => $cutoff->toDateString()
            ]);
            
        } catch (\Exception $e) {
            Log::error('Avatar cleanup failed', ['error' => $e->getMessage()]);
        }
        
        return $cleaned;
    }
}