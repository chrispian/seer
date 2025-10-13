<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Orchestration\OrchestrationTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrchestrationTemplateController extends Controller
{
    public function __construct(
        protected OrchestrationTemplateService $templateService
    ) {}

    public function index(): JsonResponse
    {
        $templates = $this->templateService->getAvailableTemplates();

        return response()->json([
            'success' => true,
            'templates' => $templates,
        ]);
    }

    public function show(string $type, string $name): JsonResponse
    {
        $content = $this->templateService->loadTemplate($type, $name);

        if (!$content) {
            return response()->json([
                'success' => false,
                'message' => 'Template not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'type' => $type,
            'name' => $name,
            'content' => $content,
        ]);
    }
}
