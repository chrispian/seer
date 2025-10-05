<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ImportSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                File::create()
                    ->types(['json'])
                    ->max(1024) // 1MB max
                    ->rules(['mimes:json,txt']), // Allow text files with .json extension
            ],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('file')) {
                $file = $this->file('file');

                // Additional security checks
                if (! $this->isValidJsonFile($file)) {
                    $validator->errors()->add('file', 'The uploaded file must contain valid JSON data.');
                }

                if ($this->containsSuspiciousContent($file)) {
                    $validator->errors()->add('file', 'The uploaded file contains potentially unsafe content.');
                }
            }
        });
    }

    /**
     * Check if the uploaded file contains valid JSON
     */
    private function isValidJsonFile($file): bool
    {
        try {
            $content = file_get_contents($file->getPathname());
            $decoded = json_decode($content, true);

            return json_last_error() === JSON_ERROR_NONE && is_array($decoded);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check for suspicious content that could indicate malicious files
     */
    private function containsSuspiciousContent($file): bool
    {
        try {
            $content = file_get_contents($file->getPathname());

            // Check for common suspicious patterns
            $suspiciousPatterns = [
                '/<\?php/i',
                '/<script/i',
                '/javascript:/i',
                '/data:text\/html/i',
                '/eval\s*\(/i',
                '/exec\s*\(/i',
                '/system\s*\(/i',
                '/shell_exec/i',
                '/base64_decode/i',
            ];

            foreach ($suspiciousPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            // If we can't read the file, consider it suspicious
            return true;
        }
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a settings file to import.',
            'file.mimes' => 'Only JSON files are supported for settings import.',
            'file.max' => 'Settings file must be smaller than 1MB.',
        ];
    }
}
