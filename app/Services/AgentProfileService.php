<?php

namespace App\Services;

use App\Enums\AgentMode;
use App\Enums\AgentStatus;
use App\Enums\AgentType;
use App\Models\AgentProfile;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum as EnumRule;

class AgentProfileService
{
    private const FIELDS = [
        'name',
        'slug',
        'type',
        'mode',
        'description',
        'capabilities',
        'constraints',
        'tools',
        'metadata',
        'status',
    ];

    /**
     * Return agent profiles ordered by name with optional filters applied.
     */
    public function list(array $filters = []): EloquentCollection
    {
        $query = AgentProfile::query()->orderBy('name');

        if (!empty($filters['status'])) {
            $statuses = $this->normaliseMultiple($filters['status'], AgentStatus::class);
            $query->whereIn('status', $statuses);
        }

        if (!empty($filters['type'])) {
            $types = $this->normaliseMultiple($filters['type'], AgentType::class);
            $query->whereIn('type', $types);
        }

        if (!empty($filters['mode'])) {
            $modes = $this->normaliseMultiple($filters['mode'], AgentMode::class);
            $query->whereIn('mode', $modes);
        }

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['limit'])) {
            $query->limit((int) $filters['limit']);
        }

        return $query->get();
    }

    /**
     * Locate an agent profile by slug.
     */
    public function findBySlug(string $slug): ?AgentProfile
    {
        return AgentProfile::where('slug', $slug)->first();
    }

    /**
     * Create a new agent profile after validation.
     */
    public function create(array $attributes): AgentProfile
    {
        $payload = $this->prepare($attributes);
        $validated = $this->validate($payload);

        return AgentProfile::create($validated);
    }

    /**
     * Update an existing agent profile and return the fresh instance.
     */
    public function update(AgentProfile $profile, array $attributes): AgentProfile
    {
        $payload = $this->prepare($attributes, $profile);
        $validated = $this->validate($payload, $profile);

        $profile->fill($validated);
        $profile->save();

        return $profile->refresh();
    }

    /**
     * Archive an agent profile without deleting related history.
     */
    public function archive(AgentProfile $profile): AgentProfile
    {
        $profile->status = AgentStatus::Archived;
        $profile->save();

        return $profile->refresh();
    }

    /**
     * Restore an archived or inactive agent to active state.
     */
    public function activate(AgentProfile $profile): AgentProfile
    {
        $profile->status = AgentStatus::Active;
        $profile->save();

        return $profile->refresh();
    }

    /**
     * Permanently delete an agent profile; cascades will clean up assignments.
     */
    public function delete(AgentProfile $profile): void
    {
        $profile->delete();
    }

    /**
     * Provide available type options with metadata for UIs.
     */
    public function availableTypes(): array
    {
        return array_map(static function (AgentType $type) {
            return [
                'value' => $type->value,
                'label' => $type->label(),
                'description' => $type->description(),
                'default_mode' => $type->defaultMode()->value,
            ];
        }, AgentType::cases());
    }

    /**
     * Provide available mode options with copy.
     */
    public function availableModes(): array
    {
        return array_map(static function (AgentMode $mode) {
            return [
                'value' => $mode->value,
                'label' => $mode->label(),
                'description' => $mode->description(),
            ];
        }, AgentMode::cases());
    }

    /**
     * Provide available status options.
     */
    public function availableStatuses(): array
    {
        return array_map(static function (AgentStatus $status) {
            return [
                'value' => $status->value,
                'label' => $status->label(),
            ];
        }, AgentStatus::cases());
    }

    /**
     * Validation rules for creating/updating an agent profile.
     */
    public function rules(?AgentProfile $profile = null): array
    {
        $uniqueSlug = Rule::unique('agent_profiles', 'slug');

        if ($profile) {
            $uniqueSlug = $uniqueSlug->ignore($profile->getKey());
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $uniqueSlug],
            'type' => ['required', new EnumRule(AgentType::class)],
            'mode' => ['required', new EnumRule(AgentMode::class)],
            'description' => ['nullable', 'string'],
            'capabilities' => ['nullable', 'array'],
            'capabilities.*' => ['string', 'max:255'],
            'constraints' => ['nullable', 'array'],
            'constraints.*' => ['string', 'max:255'],
            'tools' => ['nullable', 'array'],
            'tools.*' => ['string', 'max:255'],
            'metadata' => ['nullable', 'array'],
            'status' => ['required', new EnumRule(AgentStatus::class)],
        ];
    }

    private function validate(array $payload, ?AgentProfile $profile = null): array
    {
        return Validator::make($payload, $this->rules($profile))->validate();
    }

    private function prepare(array $attributes, ?AgentProfile $profile = null): array
    {
        $filtered = Arr::only($attributes, self::FIELDS);

        if (array_key_exists('name', $filtered)) {
            $filtered['name'] = trim((string) $filtered['name']);
        } elseif ($profile) {
            $filtered['name'] = $profile->name;
        } else {
            throw new \InvalidArgumentException('Agent name is required.');
        }

        $slugSource = $filtered['slug'] ?? null;

        if (is_string($slugSource) && $slugSource !== '') {
            $slugSource = Str::slug($slugSource) ?: null;
        }

        if (empty($slugSource)) {
            $slugSource = $filtered['name'] ?? null;
        }

        $filtered['slug'] = $this->makeUniqueSlug($slugSource, $profile?->getKey());

        if (array_key_exists('type', $filtered)) {
            $filtered['type'] = $this->normaliseValue($filtered['type'], AgentType::class);
        } elseif (!$profile) {
            throw new \InvalidArgumentException('Agent type is required.');
        } else {
            $filtered['type'] = $profile->type->value;
        }

        if (array_key_exists('mode', $filtered)) {
            $filtered['mode'] = $this->normaliseValue($filtered['mode'], AgentMode::class);
        }

        if (empty($filtered['mode'] ?? null) && !empty($filtered['type'])) {
            $type = AgentType::tryFrom($filtered['type']);
            if ($type) {
                $filtered['mode'] = $type->defaultMode()->value;
            }
        }

        if (array_key_exists('status', $filtered)) {
            $filtered['status'] = $this->normaliseValue($filtered['status'], AgentStatus::class);
        } elseif ($profile) {
            $filtered['status'] = $profile->status->value;
        } else {
            $filtered['status'] = AgentStatus::Active->value;
        }

        foreach (['capabilities', 'constraints', 'tools'] as $listField) {
            if (array_key_exists($listField, $filtered)) {
                $filtered[$listField] = $this->prepareList($filtered[$listField]);
            }
        }

        if (array_key_exists('metadata', $filtered)) {
            $filtered['metadata'] = is_array($filtered['metadata']) ? $filtered['metadata'] : [];
        }

        return $filtered;
    }

    private function makeUniqueSlug(?string $slugSource, ?string $ignoreId = null): ?string
    {
        if (! $slugSource) {
            return null;
        }

        $base = Str::slug($slugSource);

        if ($base === '') {
            return null;
        }

        $candidate = $base;
        $suffix = 2;

        while (
            AgentProfile::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $candidate)
                ->exists()
        ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function prepareList(mixed $value): array
    {
        $items = is_array($value) ? $value : [$value];

        $items = array_map(static function ($item) {
            if (is_string($item)) {
                return trim($item);
            }

            return $item;
        }, $items);

        return array_values(array_filter($items, static fn ($item) => $item !== null && $item !== ''));
    }

    /**
     * @param class-string $enum
     */
    private function normaliseValue(mixed $value, string $enum): string
    {
        if ($value instanceof $enum) {
            return $value->value;
        }

        if (is_string($value)) {
            $enumCase = $enum::tryFrom($value);

            if ($enumCase) {
                return $enumCase->value;
            }
        }

        if ($enum === AgentType::class && is_string($value)) {
            $fromLabel = AgentType::fromLabel($value);
            if ($fromLabel) {
                return $fromLabel->value;
            }
        }

        throw new \InvalidArgumentException(sprintf('Value "%s" is not a valid %s.', (string) $value, class_basename($enum)));
    }

    /**
     * @param class-string $enum
     */
    private function normaliseMultiple(mixed $values, string $enum): array
    {
        $items = is_array($values) ? $values : [$values];

        return array_map(fn ($value) => $this->normaliseValue($value, $enum), $items);
    }
}
