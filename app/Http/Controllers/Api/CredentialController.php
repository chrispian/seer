<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCredentialRequest;
use App\Http\Requests\UpdateCredentialRequest;
use App\Http\Resources\CredentialResource;
use App\Models\AICredential;
use App\Models\Provider;
use App\Services\ProviderTestingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CredentialController extends Controller
{
    protected ProviderTestingService $testingService;

    public function __construct(ProviderTestingService $testingService)
    {
        $this->testingService = $testingService;
    }

    /**
     * Display credentials for a specific provider
     */
    public function index(string $provider): JsonResponse
    {
        try {
            $providerConfig = Provider::where('provider', $provider)->first();

            if (! $providerConfig) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Provider '{$provider}' not found",
                ], 404);
            }

            $credentials = $providerConfig->credentials()->get();

            return response()->json([
                'data' => CredentialResource::collection($credentials),
                'meta' => [
                    'provider' => $provider,
                    'total_count' => $credentials->count(),
                    'active_count' => $credentials->where('is_active', true)->count(),
                ],
                'status' => 'success',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to list credentials', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve credentials',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store new credentials for a provider
     */
    public function store(StoreCredentialRequest $request, string $provider): JsonResponse
    {
        try {
            $validatedData = $request->validated();

            // Validate credential format
            $validation = $this->testingService->validateCredentialFormat(
                $provider,
                $validatedData['credentials']
            );

            if (! $validation['valid']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credential format',
                    'errors' => $validation['errors'],
                ], 422);
            }

            // Test credentials if requested
            $testResult = null;
            if ($request->get('test_on_create', true)) {
                $testResult = $this->testingService->testCredentials(
                    $provider,
                    $validatedData['credentials']
                );

                if ($testResult['status'] === 'failed') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Credential test failed',
                        'test_result' => $testResult,
                    ], 400);
                }
            }

            // Store credentials
            $credential = AICredential::storeCredentialsEnhanced(
                $provider,
                $validatedData['credentials'],
                $validatedData['credential_type'] ?? 'api_key',
                $validatedData['metadata'] ?? [],
                $validatedData['ui_metadata'] ?? [],
                isset($validatedData['expires_at']) ? new \DateTime($validatedData['expires_at']) : null
            );

            // Update UI metadata with test results
            if ($testResult) {
                $uiMetadata = $credential->getUIMetadata();
                $uiMetadata['last_tested'] = $testResult['tested_at'];
                $uiMetadata['test_results'] = $testResult;
                $credential->update(['ui_metadata' => $uiMetadata]);
            }

            return response()->json([
                'data' => new CredentialResource($credential),
                'test_result' => $testResult,
                'status' => 'success',
                'message' => 'Credentials stored successfully',
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to store credentials', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to store credentials',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update existing credentials
     */
    public function update(UpdateCredentialRequest $request, string $provider, int $credentialId): JsonResponse
    {
        try {
            $credential = AICredential::where('provider', $provider)
                ->where('id', $credentialId)
                ->first();

            if (! $credential) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Credential not found',
                ], 404);
            }

            $validatedData = $request->validated();

            // If updating credentials, validate format
            if (isset($validatedData['credentials'])) {
                $validation = $this->testingService->validateCredentialFormat(
                    $provider,
                    $validatedData['credentials']
                );

                if (! $validation['valid']) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid credential format',
                        'errors' => $validation['errors'],
                    ], 422);
                }

                // Test new credentials if requested
                $testResult = null;
                if ($request->get('test_on_update', true)) {
                    $testResult = $this->testingService->testCredentials(
                        $provider,
                        $validatedData['credentials']
                    );

                    if ($testResult['status'] === 'failed') {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Updated credential test failed',
                            'test_result' => $testResult,
                        ], 400);
                    }
                }

                // Update credentials
                $credential->setCredentials($validatedData['credentials']);

                // Update UI metadata with test results
                if ($testResult) {
                    $uiMetadata = $credential->getUIMetadata();
                    $uiMetadata['last_tested'] = $testResult['tested_at'];
                    $uiMetadata['test_results'] = $testResult;
                    $validatedData['ui_metadata'] = array_merge($uiMetadata, $validatedData['ui_metadata'] ?? []);
                }
            }

            // Update other fields
            $updateData = array_intersect_key($validatedData, array_flip([
                'credential_type', 'metadata', 'ui_metadata', 'expires_at', 'is_active',
            ]));

            if (isset($updateData['expires_at'])) {
                $updateData['expires_at'] = new \DateTime($updateData['expires_at']);
            }

            $credential->update($updateData);

            return response()->json([
                'data' => new CredentialResource($credential->fresh()),
                'test_result' => $testResult ?? null,
                'status' => 'success',
                'message' => 'Credentials updated successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update credentials', [
                'provider' => $provider,
                'credential_id' => $credentialId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update credentials',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove credentials
     */
    public function destroy(Request $request, string $provider, int $credentialId): JsonResponse
    {
        try {
            $credential = AICredential::where('provider', $provider)
                ->where('id', $credentialId)
                ->first();

            if (! $credential) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Credential not found',
                ], 404);
            }

            $hardDelete = $request->get('hard_delete', false);

            if ($hardDelete) {
                $credential->delete();
                $message = 'Credentials permanently deleted';
            } else {
                $credential->update(['is_active' => false]);
                $message = 'Credentials deactivated';
            }

            // Check if provider still has active credentials
            $providerConfig = $credential->getProvider();
            $activeCredentialCount = $providerConfig->activeCredentials()->count();

            if ($activeCredentialCount === 0) {
                Log::warning('Provider has no active credentials remaining', [
                    'provider' => $provider,
                    'provider_config_id' => $providerConfig->id,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'provider_status' => [
                    'has_active_credentials' => $activeCredentialCount > 0,
                    'active_credential_count' => $activeCredentialCount,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete credentials', [
                'provider' => $provider,
                'credential_id' => $credentialId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete credentials',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Test specific credentials
     */
    public function test(string $provider, int $credentialId): JsonResponse
    {
        try {
            $credential = AICredential::where('provider', $provider)
                ->where('id', $credentialId)
                ->first();

            if (! $credential) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Credential not found',
                ], 404);
            }

            $credentials = $credential->getCredentials();
            $result = $this->testingService->testCredentials($provider, $credentials);

            // Update test results in UI metadata
            $uiMetadata = $credential->getUIMetadata();
            $uiMetadata['last_tested'] = $result['tested_at'];
            $uiMetadata['test_results'] = $result;
            $credential->update(['ui_metadata' => $uiMetadata]);

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
            Log::error('Credential test failed', [
                'provider' => $provider,
                'credential_id' => $credentialId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Credential test failed',
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
            'healthy' => "Credentials for '{$result['provider']}' are valid",
            'timeout' => "Credential test for '{$result['provider']}' timed out",
            'failed' => "Credential test for '{$result['provider']}' failed: ".($result['error'] ?? 'Unknown error'),
            default => "Credential test for '{$result['provider']}' status unknown",
        };
    }
}
