<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProviderRequest;
use App\Http\Resources\ProviderResource;
use App\Services\ProviderManagementService;
use App\Services\ProviderTestingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProviderController extends Controller
{
    protected ProviderManagementService $providerService;

    protected ProviderTestingService $testingService;

    public function __construct(
        ProviderManagementService $providerService,
        ProviderTestingService $testingService
    ) {
        $this->providerService = $providerService;
        $this->testingService = $testingService;
    }

    /**
     * Display a listing of providers
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $providers = $this->providerService->getAllProviders();

            // Filter by status if requested
            if ($request->has('status')) {
                $status = $request->get('status');
                $providers = $providers->filter(function ($provider) use ($status) {
                    return match ($status) {
                        'enabled' => $provider->enabled,
                        'disabled' => ! $provider->enabled,
                        'available' => $provider['is_available'],
                        'healthy' => $provider['health_status'] === 'healthy',
                        'unhealthy' => $provider['health_status'] === 'unhealthy',
                        default => true,
                    };
                });
            }

            // Sort providers
            $sortBy = $request->get('sort', 'name');
            $sortDirection = $request->get('direction', 'asc');

            $providers = $providers->sortBy($sortBy, SORT_REGULAR, $sortDirection === 'desc');

            return response()->json([
                'data' => ProviderResource::collection($providers->values()),
                'meta' => [
                    'total' => $providers->count(),
                    'enabled_count' => $providers->where('enabled', true)->count(),
                    'available_count' => $providers->where('is_available', true)->count(),
                ],
                'status' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list providers', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve providers',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified provider
     */
    public function show(string $provider): JsonResponse
    {
        try {
            $providerData = $this->providerService->getProvider($provider);

            if (! $providerData) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Provider '{$provider}' not found",
                ], 404);
            }

            return response()->json([
                'data' => new ProviderResource($providerData),
                'status' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get provider details', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve provider details',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified provider configuration
     */
    public function update(UpdateProviderRequest $request, string $provider): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            $providerConfig = $this->providerService->updateProviderConfig($provider, $validatedData);
            $providerData = $this->providerService->getProvider($provider);

            return response()->json([
                'data' => new ProviderResource($providerData),
                'status' => 'success',
                'message' => 'Provider configuration updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update provider configuration', [
                'provider' => $provider,
                'data' => $request->all(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update provider configuration',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Toggle provider enabled/disabled state
     */
    public function toggle(string $provider): JsonResponse
    {
        try {
            $providerConfig = $this->providerService->toggleProvider($provider);
            $providerData = $this->providerService->getProvider($provider);

            return response()->json([
                'data' => new ProviderResource($providerData),
                'status' => 'success',
                'message' => $providerConfig->enabled
                    ? 'Provider enabled successfully'
                    : 'Provider disabled successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle provider', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to toggle provider state',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test provider connectivity
     */
    public function test(string $provider): JsonResponse
    {
        try {
            $result = $this->testingService->testCredentials($provider);

            $statusCode = match ($result['status']) {
                'healthy' => 200,
                'timeout' => 408,
                'failed' => 400,
                default => 500,
            };

            return response()->json([
                'data' => $result,
                'status' => $result['status'] === 'healthy' ? 'success' : 'error',
                'message' => $this->getTestResultMessage($result),
            ], $statusCode);

        } catch (\Exception $e) {
            Log::error('Provider test failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Provider test failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get provider health status
     */
    public function health(string $provider): JsonResponse
    {
        try {
            $healthData = $this->testingService->getHealthHistory($provider);
            $currentHealth = $this->testingService->checkProviderHealth($provider);

            return response()->json([
                'data' => array_merge($healthData, [
                    'current_check' => $currentHealth,
                ]),
                'status' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get provider health', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve provider health status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Run bulk health check
     */
    public function bulkHealthCheck(Request $request): JsonResponse
    {
        try {
            $providers = $request->get('providers');
            $result = $this->testingService->bulkHealthCheck($providers);

            return response()->json([
                'data' => $result,
                'status' => 'success',
                'message' => sprintf(
                    'Health check completed for %d providers (%d healthy, %d failed)',
                    $result['summary']['total_providers'],
                    $result['summary']['healthy_count'],
                    $result['summary']['failed_count']
                ),
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk health check failed', [
                'providers' => $request->get('providers'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Bulk health check failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Sync provider capabilities from configuration
     */
    public function syncCapabilities(Request $request): JsonResponse
    {
        try {
            $provider = $request->get('provider');
            $results = $this->providerService->markProvidersSynced($provider);

            return response()->json([
                'data' => $results,
                'status' => 'success',
                'message' => $provider
                    ? "Sync timestamp updated for provider '{$provider}'"
                    : 'Sync timestamp updated for all providers',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update provider sync timestamp', [
                'provider' => $request->get('provider'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update provider sync timestamp',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get provider statistics
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->providerService->getProviderStatistics();

            return response()->json([
                'data' => $stats,
                'status' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get provider statistics', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve provider statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get appropriate message for test result
     */
    protected function getTestResultMessage(array $result): string
    {
        return match ($result['status']) {
            'healthy' => "Provider '{$result['provider']}' is healthy",
            'timeout' => "Provider '{$result['provider']}' connection timed out",
            'failed' => "Provider '{$result['provider']}' test failed: ".($result['error'] ?? 'Unknown error'),
            default => "Provider '{$result['provider']}' status unknown",
        };
    }
}
