<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTypePackRequest extends FormRequest
{
    public function authorize(): bool
    {
        // TODO: Add authorization logic (policy check)
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',
            'description' => 'nullable|string|max:500',
            'version' => 'sometimes|string|max:20',
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
}
