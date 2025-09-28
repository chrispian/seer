<?php

namespace App\Http\Controllers;

use App\Models\Fragment;
use App\Models\Bookmark;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class WidgetApiController extends Controller
{
    public function todayActivity(Request $request)
    {
        $today = Carbon::today();
        
        // Get today's fragments with AI response metadata
        $fragments = Fragment::whereDate('created_at', $today)
            ->whereJsonContains('metadata->turn', 'response')
            ->get();

        $messages = Fragment::whereDate('created_at', $today)
            ->whereJsonContains('metadata->turn', 'prompt')
            ->count();

        $commands = 0; // TODO: Count commands from fragments or command logs

        $totalTokensIn = 0;
        $totalTokensOut = 0;
        $totalCost = 0.0;
        $latencies = [];
        $modelsUsed = [];

        foreach ($fragments as $fragment) {
            $metadata = $fragment->metadata ?? [];
            
            if (isset($metadata['token_usage'])) {
                $totalTokensIn += $metadata['token_usage']['input'] ?? 0;
                $totalTokensOut += $metadata['token_usage']['output'] ?? 0;
            }
            
            if (isset($metadata['cost_usd'])) {
                $totalCost += $metadata['cost_usd'];
            }
            
            if (isset($metadata['latency_ms'])) {
                $latencies[] = $metadata['latency_ms'];
            }
            
            if (isset($metadata['model']) && !in_array($metadata['model'], $modelsUsed)) {
                $modelsUsed[] = $metadata['model'];
            }
        }

        $avgResponseTime = count($latencies) > 0 ? array_sum($latencies) / count($latencies) : 0;

        // Generate hourly chart data for today
        $chartData = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $hourStart = $today->copy()->addHours($hour);
            $hourEnd = $hourStart->copy()->addHour();
            
            $hourFragments = Fragment::whereBetween('created_at', [$hourStart, $hourEnd])
                ->whereJsonContains('metadata->turn', 'response')
                ->get();
            
            $hourMessages = Fragment::whereBetween('created_at', [$hourStart, $hourEnd])
                ->whereJsonContains('metadata->turn', 'prompt')
                ->count();
            
            $hourTokens = 0;
            $hourCost = 0.0;
            
            foreach ($hourFragments as $fragment) {
                $metadata = $fragment->metadata ?? [];
                if (isset($metadata['token_usage'])) {
                    $hourTokens += ($metadata['token_usage']['input'] ?? 0) + ($metadata['token_usage']['output'] ?? 0);
                }
                if (isset($metadata['cost_usd'])) {
                    $hourCost += $metadata['cost_usd'];
                }
            }
            
            $chartData[] = [
                'hour' => $hourStart->format('H:00'),
                'messages' => $hourMessages,
                'tokens' => $hourTokens,
                'cost' => $hourCost,
            ];
        }

        return response()->json([
            'messages' => $messages,
            'commands' => $commands,
            'totalTokensIn' => $totalTokensIn,
            'totalTokensOut' => $totalTokensOut,
            'totalCost' => $totalCost,
            'avgResponseTime' => $avgResponseTime,
            'modelsUsed' => $modelsUsed,
            'chartData' => $chartData,
        ]);
    }

    public function bookmarks(Request $request)
    {
        $query = $request->get('query', '');
        $vaultId = $request->get('vault_id');
        $projectId = $request->get('project_id');
        $limit = min($request->get('limit', 5), 50);
        $offset = $request->get('offset', 0);

        $bookmarksQuery = Bookmark::query()
            ->orderBy('last_viewed_at', 'desc')
            ->orderBy('updated_at', 'desc');

        // Add vault and project scoping
        if ($vaultId) {
            $bookmarksQuery->where('vault_id', $vaultId);
        }
        
        if ($projectId) {
            $bookmarksQuery->where('project_id', $projectId);
        }
        
        if (!empty($query)) {
            $bookmarksQuery->where('name', 'ILIKE', "%{$query}%");
        }

        $total = $bookmarksQuery->count();
        $bookmarks = $bookmarksQuery->skip($offset)->take($limit)->get();

        // Enhance bookmarks with fragment data
        $enhancedBookmarks = $bookmarks->map(function ($bookmark) {
            $fragmentIds = $bookmark->fragment_ids ?? [];
            $firstFragment = null;
            
            if (!empty($fragmentIds)) {
                $firstFragment = Fragment::find($fragmentIds[0]);
            }
            
            return [
                'id' => $bookmark->id,
                'name' => $bookmark->name,
                'fragment_ids' => $bookmark->fragment_ids,
                'last_viewed_at' => $bookmark->last_viewed_at?->toISOString(),
                'created_at' => $bookmark->created_at->toISOString(),
                'updated_at' => $bookmark->updated_at->toISOString(),
                'fragment_title' => $firstFragment?->title,
                'fragment_preview' => $firstFragment ? substr($firstFragment->message, 0, 100) : null,
                'vault_id' => $bookmark->vault_id,
                'project_id' => $bookmark->project_id
            ];
        });

        return response()->json([
            'bookmarks' => $enhancedBookmarks,
            'total' => $total,
            'hasMore' => ($offset + $limit) < $total,
        ]);
    }

    public function toolCalls(Request $request)
    {
        $sessionId = $request->get('session_id');
        $type = $request->get('type');
        $provider = $request->get('provider');
        $limit = min($request->get('limit', 20), 100);
        $offset = $request->get('offset', 0);

        // Query fragments that contain tool call or reasoning metadata
        $query = Fragment::whereNotNull('metadata')
            ->where(function ($q) {
                $q->whereJsonContains('metadata->turn', 'response')
                  ->orWhereJsonContains('metadata->reasoning', true);
            })
            ->orderBy('created_at', 'desc');

        if ($sessionId) {
            $query->whereJsonContains('metadata->session_id', $sessionId);
        }

        if ($provider) {
            $query->whereJsonContains('metadata->provider', $provider);
        }

        $fragments = $query->skip($offset)->take($limit)->get();

        $toolCalls = $fragments->map(function ($fragment) {
            $metadata = $fragment->metadata ?? [];
            
            // Determine type based on metadata
            $type = 'model_response';
            if (isset($metadata['tools_used']) && !empty($metadata['tools_used'])) {
                $type = 'tool_call';
            } elseif (isset($metadata['reasoning'])) {
                $type = 'cot_reasoning';
            }

            return [
                'id' => $fragment->id,
                'timestamp' => $fragment->created_at->toISOString(),
                'type' => $type,
                'title' => $fragment->title ?: 'AI Response',
                'summary' => substr($fragment->message, 0, 100) . '...',
                'provider' => $metadata['provider'] ?? 'unknown',
                'model' => $metadata['model'] ?? 'unknown',
                'tokenUsage' => [
                    'input' => $metadata['token_usage']['input'] ?? 0,
                    'output' => $metadata['token_usage']['output'] ?? 0,
                ],
                'cost' => $metadata['cost_usd'] ?? 0,
                'latency' => $metadata['latency_ms'] ?? 0,
                'metadata' => [
                    'reasoning' => $metadata['reasoning'] ?? null,
                    'tools_used' => $metadata['tools_used'] ?? [],
                    'weights' => $metadata['weights'] ?? [],
                    'confidence' => $metadata['confidence'] ?? null,
                ],
                'isExpanded' => false,
            ];
        });

        return response()->json($toolCalls);
    }
}