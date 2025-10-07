<?php

namespace App\Http\Controllers\Orchestration;

use App\Http\Controllers\Controller;
use App\Models\AgentProfile;
use App\Models\Message;
use App\Models\WorkItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MessagingController extends Controller
{
    public function sendToAgent(Request $request, string $agentId): JsonResponse
    {
        $validated = $request->validate([
            'stream' => 'required|string|max:255',
            'type' => 'required|string|max:100',
            'task_id' => 'nullable|uuid|exists:work_items,id',
            'project_id' => 'nullable|uuid',
            'from_agent_id' => 'nullable|uuid|exists:agent_profiles,id',
            'headers' => 'nullable|array',
            'envelope' => 'required|array',
        ]);

        $agent = AgentProfile::findOrFail($agentId);

        $message = Message::create([
            'stream' => $validated['stream'],
            'type' => $validated['type'],
            'task_id' => $validated['task_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'to_agent_id' => $agent->id,
            'from_agent_id' => $validated['from_agent_id'] ?? null,
            'headers' => $validated['headers'] ?? [],
            'envelope' => $validated['envelope'],
        ]);

        return response()->json([
            'success' => true,
            'message_id' => $message->id,
            'stream' => $message->stream,
            'created_at' => $message->created_at->toIso8601String(),
        ], 201);
    }

    public function listAgentInbox(Request $request, string $agentId): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|string|in:unread,all',
            'stream' => 'sometimes|string',
            'type' => 'sometimes|string',
            'task_id' => 'sometimes|uuid',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $agent = AgentProfile::findOrFail($agentId);
        
        $query = Message::query()->toAgent($agent->id);

        if (($validated['status'] ?? 'unread') === 'unread') {
            $query->unread();
        }

        if (isset($validated['stream'])) {
            $query->byStream($validated['stream']);
        }

        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (isset($validated['task_id'])) {
            $query->byTask($validated['task_id']);
        }

        $perPage = $validated['per_page'] ?? 25;
        $messages = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => $messages->map(fn($msg) => [
                'id' => $msg->id,
                'stream' => $msg->stream,
                'type' => $msg->type,
                'task_id' => $msg->task_id,
                'project_id' => $msg->project_id,
                'from_agent_id' => $msg->from_agent_id,
                'headers' => $msg->headers,
                'envelope' => $msg->envelope,
                'read_at' => $msg->read_at?->toIso8601String(),
                'created_at' => $msg->created_at->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $messages->currentPage(),
                'last_page' => $messages->lastPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'unread_count' => Message::toAgent($agent->id)->unread()->count(),
            ],
        ]);
    }

    public function markAsRead(string $messageId): JsonResponse
    {
        $message = Message::findOrFail($messageId);

        if ($message->isRead()) {
            return response()->json([
                'success' => true,
                'message' => 'Message already read',
                'read_at' => $message->read_at->toIso8601String(),
            ]);
        }

        $message->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Message marked as read',
            'read_at' => $message->read_at->toIso8601String(),
        ]);
    }

    public function broadcast(Request $request, string $projectId): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|max:100',
            'task_id' => 'nullable|uuid|exists:work_items,id',
            'from_agent_id' => 'nullable|uuid|exists:agent_profiles,id',
            'headers' => 'nullable|array',
            'envelope' => 'required|array',
            'to_agent_ids' => 'sometimes|array',
            'to_agent_ids.*' => 'uuid|exists:agent_profiles,id',
        ]);

        $agents = isset($validated['to_agent_ids']) 
            ? AgentProfile::whereIn('id', $validated['to_agent_ids'])->get()
            : AgentProfile::where('status', 'active')->get();

        $messageIds = [];

        foreach ($agents as $agent) {
            $message = Message::create([
                'stream' => "projects.{$projectId}.broadcast",
                'type' => $validated['type'],
                'task_id' => $validated['task_id'] ?? null,
                'project_id' => $projectId,
                'to_agent_id' => $agent->id,
                'from_agent_id' => $validated['from_agent_id'] ?? null,
                'headers' => $validated['headers'] ?? [],
                'envelope' => $validated['envelope'],
            ]);

            $messageIds[] = $message->id;
        }

        return response()->json([
            'success' => true,
            'broadcast_count' => count($messageIds),
            'message_ids' => $messageIds,
        ], 201);
    }
}
