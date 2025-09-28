<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class FileUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
                File::types(['jpg', 'jpeg', 'png', 'gif', 'pdf', 'txt', 'md', 'doc', 'docx'])
                    ->max(10 * 1024) // 10MB max
            ],
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        
        // Generate unique filename
        $filename = Str::uuid() . '.' . $extension;
        
        // Store file in uploads directory
        $path = $file->storeAs('uploads', $filename, 'public');
        
        // Generate public URL
        $url = Storage::url($path);
        
        // Determine markdown format based on file type
        $markdown = $this->generateMarkdown($originalName, $url, $mimeType);
        
        return response()->json([
            'success' => true,
            'file_id' => $filename,
            'original_name' => $originalName,
            'url' => $url,
            'markdown' => $markdown,
            'mime_type' => $mimeType,
            'size' => $file->getSize(),
        ]);
    }

    private function generateMarkdown(string $originalName, string $url, string $mimeType): string
    {
        // Generate appropriate markdown based on file type
        if (str_starts_with($mimeType, 'image/')) {
            return "![{$originalName}]({$url})";
        }
        
        if ($mimeType === 'application/pdf') {
            return "[📄 {$originalName}]({$url})";
        }
        
        if (str_starts_with($mimeType, 'text/')) {
            return "[📝 {$originalName}]({$url})";
        }
        
        // Default file link
        return "[📎 {$originalName}]({$url})";
    }
}