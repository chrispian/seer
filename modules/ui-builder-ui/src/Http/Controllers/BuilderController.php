<?php

namespace HollisLabs\UiBuilder\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use HollisLabs\UiBuilder\Models\BuilderSession;
use HollisLabs\UiBuilder\Models\BuilderPageComponent;
use HollisLabs\UiBuilder\Models\Page;
use HollisLabs\UiBuilder\Models\Component;
use Illuminate\Support\Str;

class BuilderController extends Controller
{
    public function saveProgress(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id') ?? Str::uuid()->toString();
        $userId = auth()->id() ?? 'guest';

        $session = BuilderSession::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'user_id' => $userId,
                'page_key' => $request->input('page_key'),
                'title' => $request->input('title'),
                'overlay' => $request->input('overlay'),
                'route' => $request->input('route'),
                'module_key' => $request->input('module_key'),
                'layout_type' => $request->input('layout_type'),
                'layout_id' => $request->input('layout_id', 'root-layout'),
                'state_json' => $request->input('state', []),
                'expires_at' => now()->addDays(7),
            ]
        );

        return response()->json([
            'success' => true,
            'session_id' => $sessionId,
            'message' => 'Progress saved',
        ]);
    }

    public function loadProgress(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');

        if (! $sessionId) {
            return response()->json([
                'error' => 'session_id required',
            ], 400);
        }

        $session = BuilderSession::bySessionId($sessionId)->active()->first();

        if (! $session) {
            return response()->json([
                'error' => 'Session not found or expired',
            ], 404);
        }

        return response()->json([
            'session_id' => $session->session_id,
            'page_key' => $session->page_key,
            'title' => $session->title,
            'overlay' => $session->overlay,
            'route' => $session->route,
            'module_key' => $session->module_key,
            'layout_type' => $session->layout_type,
            'layout_id' => $session->layout_id,
            'state' => $session->state_json,
            'component_count' => $session->components()->count(),
        ]);
    }

    public function getPageComponents(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');

        if (! $sessionId) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                ],
            ]);
        }

        $session = BuilderSession::bySessionId($sessionId)->first();

        if (! $session) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'total' => 0,
                ],
            ]);
        }

        $components = $session->components()
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get()
            ->map(function ($component) {
                return [
                    'id' => $component->id,
                    'component_id' => $component->component_id,
                    'type' => $component->component_type,
                    'order' => $component->order,
                    'has_children' => $component->children()->count() > 0,
                ];
            });

        return response()->json([
            'data' => $components,
            'meta' => [
                'total' => $components->count(),
            ],
        ]);
    }

    public function getComponentForm(Request $request, string $id): JsonResponse
    {
        $sessionId = $request->input('session_id');

        if ($id === 'new') {
            return $this->getNewComponentForm($sessionId);
        }

        $component = BuilderPageComponent::find($id);

        if (! $component) {
            return response()->json([
                'error' => 'Component not found',
            ], 404);
        }

        return $this->getEditComponentForm($component);
    }

    protected function getNewComponentForm(?string $sessionId): JsonResponse
    {
        // Get available component types
        $componentTypes = Component::select('type', 'kind')
            ->distinct()
            ->orderBy('type')
            ->get()
            ->map(fn($c) => [
                'label' => ucfirst(str_replace(['-', '.'], ' ', $c->type)) . ' (' . $c->kind . ')',
                'value' => $c->type,
            ])
            ->toArray();

        return response()->json([
            'title' => 'Add Component',
            'fields' => [
                [
                    'name' => 'component_id',
                    'label' => 'Component ID',
                    'type' => 'text',
                    'required' => true,
                    'placeholder' => 'component.table.mydata',
                    'description' => 'Unique identifier for this component instance',
                ],
                [
                    'name' => 'component_type',
                    'label' => 'Component Type',
                    'type' => 'select',
                    'required' => true,
                    'options' => $componentTypes,
                ],
                [
                    'name' => 'order',
                    'label' => 'Display Order',
                    'type' => 'number',
                    'defaultValue' => 0,
                    'description' => 'Lower numbers appear first',
                ],
                [
                    'name' => 'props_json',
                    'label' => 'Props (JSON)',
                    'type' => 'textarea',
                    'placeholder' => '{"dataSource": "Agent", "columns": [...]}',
                    'description' => 'Component properties as JSON',
                    'rows' => 10,
                ],
                [
                    'name' => 'actions_json',
                    'label' => 'Actions (JSON - Optional)',
                    'type' => 'textarea',
                    'placeholder' => '{"click": {"type": "modal", ...}}',
                    'description' => 'Event handlers as JSON',
                    'rows' => 5,
                ],
            ],
            'submitUrl' => '/api/ui/builder/page-component',
            'submitMethod' => 'POST',
        ]);
    }

    protected function getEditComponentForm(BuilderPageComponent $component): JsonResponse
    {
        return response()->json([
            'title' => 'Edit Component',
            'fields' => [
                [
                    'name' => 'component_id',
                    'label' => 'Component ID',
                    'type' => 'text',
                    'required' => true,
                    'defaultValue' => $component->component_id,
                ],
                [
                    'name' => 'component_type',
                    'label' => 'Component Type',
                    'type' => 'text',
                    'required' => true,
                    'defaultValue' => $component->component_type,
                    'readonly' => true,
                ],
                [
                    'name' => 'order',
                    'label' => 'Display Order',
                    'type' => 'number',
                    'defaultValue' => $component->order,
                ],
                [
                    'name' => 'props_json',
                    'label' => 'Props (JSON)',
                    'type' => 'textarea',
                    'defaultValue' => json_encode($component->props_json ?? [], JSON_PRETTY_PRINT),
                    'rows' => 10,
                ],
                [
                    'name' => 'actions_json',
                    'label' => 'Actions (JSON - Optional)',
                    'type' => 'textarea',
                    'defaultValue' => json_encode($component->actions_json ?? [], JSON_PRETTY_PRINT),
                    'rows' => 5,
                ],
            ],
            'submitUrl' => "/api/ui/builder/page-component/{$component->id}",
            'submitMethod' => 'PUT',
        ]);
    }

    public function createPageComponent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'component_id' => 'required|string',
            'component_type' => 'required|string',
            'order' => 'integer',
            'parent_id' => 'nullable|exists:fe_ui_builder_page_components,id',
            'props_json' => 'nullable|string',
            'actions_json' => 'nullable|string',
        ]);

        $session = BuilderSession::bySessionId($validated['session_id'])->first();

        if (! $session) {
            return response()->json([
                'error' => 'Session not found',
            ], 404);
        }

        // Parse JSON strings
        $props = null;
        $actions = null;

        if (! empty($validated['props_json'])) {
            $props = json_decode($validated['props_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'Invalid JSON in props_json: ' . json_last_error_msg(),
                ], 400);
            }
        }

        if (! empty($validated['actions_json'])) {
            $actions = json_decode($validated['actions_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'Invalid JSON in actions_json: ' . json_last_error_msg(),
                ], 400);
            }
        }

        $component = BuilderPageComponent::create([
            'session_id' => $validated['session_id'],
            'component_id' => $validated['component_id'],
            'component_type' => $validated['component_type'],
            'parent_id' => $validated['parent_id'] ?? null,
            'order' => $validated['order'] ?? 0,
            'props_json' => $props,
            'actions_json' => $actions,
        ]);

        return response()->json([
            'success' => true,
            'component' => $component,
        ], 201);
    }

    public function updatePageComponent(Request $request, int $id): JsonResponse
    {
        $component = BuilderPageComponent::find($id);

        if (! $component) {
            return response()->json([
                'error' => 'Component not found',
            ], 404);
        }

        $validated = $request->validate([
            'component_id' => 'string',
            'order' => 'integer',
            'props_json' => 'nullable|string',
            'actions_json' => 'nullable|string',
        ]);

        // Parse JSON strings
        if (isset($validated['props_json'])) {
            $props = json_decode($validated['props_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'Invalid JSON in props_json: ' . json_last_error_msg(),
                ], 400);
            }
            $validated['props_json'] = $props;
        }

        if (isset($validated['actions_json'])) {
            $actions = json_decode($validated['actions_json'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'error' => 'Invalid JSON in actions_json: ' . json_last_error_msg(),
                ], 400);
            }
            $validated['actions_json'] = $actions;
        }

        $component->update($validated);

        return response()->json([
            'success' => true,
            'component' => $component,
        ]);
    }

    public function deletePageComponent(int $id): JsonResponse
    {
        $component = BuilderPageComponent::find($id);

        if (! $component) {
            return response()->json([
                'error' => 'Component not found',
            ], 404);
        }

        // Delete children recursively
        $component->children()->each(fn($child) => $child->delete());

        $component->delete();

        return response()->json([
            'success' => true,
            'message' => 'Component deleted',
        ]);
    }

    public function saveDraft(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');

        if (! $sessionId) {
            return response()->json([
                'error' => 'session_id required',
            ], 400);
        }

        $session = BuilderSession::bySessionId($sessionId)->first();

        if (! $session) {
            return response()->json([
                'error' => 'Session not found',
            ], 404);
        }

        // Generate config and store it in the session
        $config = $session->generateConfig();
        $session->update(['config_json' => $config]);

        return response()->json([
            'success' => true,
            'message' => 'Draft saved successfully',
            'config' => $config,
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'enabled' => 'boolean',
            'guards' => 'array',
        ]);

        $session = BuilderSession::bySessionId($validated['session_id'])->first();

        if (! $session) {
            return response()->json([
                'error' => 'Session not found',
            ], 404);
        }

        // Validate required fields
        if (! $session->page_key || ! $session->title) {
            return response()->json([
                'error' => 'Missing required fields: page_key and title are required',
            ], 400);
        }

        // Generate final config
        $config = $session->generateConfig();

        // Create or update page
        $page = Page::updateOrCreate(
            ['key' => $session->page_key],
            [
                'route' => $session->route,
                'module_key' => $session->module_key,
                'enabled' => $validated['enabled'] ?? true,
                'config' => $config,
                'guards_json' => $validated['guards'] ?? [],
            ]
        );

        // Optionally delete session after publish
        // $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Page published successfully',
            'page' => [
                'id' => $page->id,
                'key' => $page->key,
                'version' => $page->version,
                'route' => $page->route,
            ],
        ], 201);
    }

    public function getPreview(Request $request): JsonResponse
    {
        $sessionId = $request->input('session_id');

        if (! $sessionId) {
            return response()->json([
                'error' => 'session_id required',
            ], 400);
        }

        $session = BuilderSession::bySessionId($sessionId)->first();

        if (! $session) {
            return response()->json([
                'error' => 'Session not found',
            ], 404);
        }

        $config = $session->generateConfig();

        return response()->json([
            'config' => $config,
            'json' => json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        ]);
    }
}
