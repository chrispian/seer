<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypePackRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Add authorization logic (policy check)
        return true;
    }

    public function rules(): array
    {
        return [
            'slug' => [
                'required',
                'string',
                'regex:/^[a-z0-9_-]+$/',
                'max:50',
                'unique:fragment_type_registry,slug',
            ],
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'version' => 'nullable|string|max:20',
            'schema' => 'nullable|array',
            'capabilities' => 'nullable|array',
            'ui' => 'nullable|array',
            'ui.icon' => 'nullable|string',
            'ui.color' => 'nullable|string',
            'ui.display_name' => 'nullable|string',
            'ui.plural_name' => 'nullable|string',
            'indexes' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'slug.required' => 'Type pack slug is required',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, hyphens, and underscores',
            'slug.unique' => 'A type pack with this slug already exists',
            'name.required' => 'Type pack name is required',
        ];
    }
}
