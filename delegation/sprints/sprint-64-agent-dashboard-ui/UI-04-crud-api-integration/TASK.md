# UI-04: Agent CRUD Operations & API Integration

**Task Code**: `UI-04`  
**Sprint**: Sprint 64 - Agent Management Dashboard UI  
**Priority**: HIGH  
**Status**: `todo`  
**Estimated**: 3-4 hours  
**Dependencies**: UI-03 (needs editor component to wire up)

## Objective

Wire up all CRUD operations to the backend API, create React hooks for data fetching and mutations, implement confirmation dialogs, and add toast notifications for user feedback.

## Requirements

### Functional Requirements

1. **API Client Methods**
   - `GET /api/agents` - List all agents
   - `POST /api/agents` - Create new agent
   - `PUT /api/agents/{id}` - Update existing agent
   - `DELETE /api/agents/{id}` - Delete agent
   - `POST /api/agents/{id}/duplicate` - Duplicate agent
   - `GET /api/agents/meta/types` - Get available agent types
   - `GET /api/agents/meta/modes` - Get available agent modes
   - `GET /api/agents/meta/statuses` - Get available agent statuses

2. **React Hooks**
   - `useAgents()` - Fetch and cache agents list
   - `useAgentMutations()` - Create, update, delete mutations
   - `useAgentMeta()` - Fetch types, modes, statuses

3. **CRUD Operations**
   - **Create**: Submit form → POST /api/agents → Update UI
   - **Read**: Fetch on mount → Display in grid
   - **Update**: Submit form → PUT /api/agents/{id} → Update UI
   - **Delete**: Confirm → DELETE /api/agents/{id} → Remove from UI
   - **Duplicate**: Duplicate → POST /api/agents/{id}/duplicate → Add to UI

4. **User Feedback**
   - **Success Notifications**: Toast on successful create/update/delete
   - **Error Notifications**: Toast on API errors
   - **Loading States**: Buttons show loading spinners
   - **Optimistic Updates**: UI updates immediately, rolls back on error

5. **Confirmation Dialogs**
   - **Delete**: "Are you sure you want to delete {name}?"
   - **Unsaved Changes**: Already handled in UI-03

## Implementation Details

### API Client

```typescript
// resources/js/lib/api/agents.ts

import { api } from '@/lib/api'

export interface Agent {
  id: string
  name: string
  slug: string
  type: string
  mode: string
  status: string
  description?: string
  capabilities?: string[]
  constraints?: string[]
  tools?: string[]
  metadata?: Record<string, any>
  created_at?: string
  updated_at?: string
}

export interface AgentType {
  value: string
  label: string
  description: string
  default_mode: string
}

export interface AgentMode {
  value: string
  label: string
  description: string
}

export interface AgentStatus {
  value: string
  label: string
}

export const agentsApi = {
  // List all agents
  async list(): Promise<Agent[]> {
    const response = await api.get('/api/agents')
    return response.data
  },

  // Get single agent by ID
  async get(id: string): Promise<Agent> {
    const response = await api.get(`/api/agents/${id}`)
    return response.data
  },

  // Create new agent
  async create(data: Partial<Agent>): Promise<Agent> {
    const response = await api.post('/api/agents', data)
    return response.data
  },

  // Update existing agent
  async update(id: string, data: Partial<Agent>): Promise<Agent> {
    const response = await api.put(`/api/agents/${id}`, data)
    return response.data
  },

  // Delete agent
  async delete(id: string): Promise<void> {
    await api.delete(`/api/agents/${id}`)
  },

  // Duplicate agent
  async duplicate(id: string): Promise<Agent> {
    const response = await api.post(`/api/agents/${id}/duplicate`)
    return response.data
  },

  // Get metadata
  async getTypes(): Promise<AgentType[]> {
    const response = await api.get('/api/agents/meta/types')
    return response.data
  },

  async getModes(): Promise<AgentMode[]> {
    const response = await api.get('/api/agents/meta/modes')
    return response.data
  },

  async getStatuses(): Promise<AgentStatus[]> {
    const response = await api.get('/api/agents/meta/statuses')
    return response.data
  },
}
```

### React Hooks

```typescript
// resources/js/hooks/useAgents.ts

import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { agentsApi, Agent } from '@/lib/api/agents'
import { toast } from '@/components/ui/use-toast'

// Fetch agents list
export function useAgents() {
  return useQuery({
    queryKey: ['agents'],
    queryFn: agentsApi.list,
    staleTime: 1000 * 60 * 5, // 5 minutes
  })
}

// Fetch agent metadata
export function useAgentMeta() {
  const types = useQuery({
    queryKey: ['agent-types'],
    queryFn: agentsApi.getTypes,
    staleTime: Infinity, // Cache forever
  })

  const modes = useQuery({
    queryKey: ['agent-modes'],
    queryFn: agentsApi.getModes,
    staleTime: Infinity,
  })

  const statuses = useQuery({
    queryKey: ['agent-statuses'],
    queryFn: agentsApi.getStatuses,
    staleTime: Infinity,
  })

  return { types, modes, statuses }
}

// Mutations for create/update/delete
export function useAgentMutations() {
  const queryClient = useQueryClient()

  // Create agent
  const createAgent = useMutation({
    mutationFn: agentsApi.create,
    onMutate: async (newAgent) => {
      // Optimistic update
      await queryClient.cancelQueries({ queryKey: ['agents'] })
      const previousAgents = queryClient.getQueryData(['agents'])
      
      queryClient.setQueryData(['agents'], (old: Agent[] = []) => [
        ...old,
        { ...newAgent, id: 'temp-' + Date.now() },
      ])

      return { previousAgents }
    },
    onError: (err, newAgent, context) => {
      // Rollback on error
      queryClient.setQueryData(['agents'], context?.previousAgents)
      toast({
        title: 'Error',
        description: 'Failed to create agent. Please try again.',
        variant: 'destructive',
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['agents'] })
      toast({
        title: 'Success',
        description: 'Agent created successfully',
      })
    },
  })

  // Update agent
  const updateAgent = useMutation({
    mutationFn: ({ id, data }: { id: string; data: Partial<Agent> }) =>
      agentsApi.update(id, data),
    onMutate: async ({ id, data }) => {
      await queryClient.cancelQueries({ queryKey: ['agents'] })
      const previousAgents = queryClient.getQueryData(['agents'])
      
      queryClient.setQueryData(['agents'], (old: Agent[] = []) =>
        old.map(agent => agent.id === id ? { ...agent, ...data } : agent)
      )

      return { previousAgents }
    },
    onError: (err, variables, context) => {
      queryClient.setQueryData(['agents'], context?.previousAgents)
      toast({
        title: 'Error',
        description: 'Failed to update agent. Please try again.',
        variant: 'destructive',
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['agents'] })
      toast({
        title: 'Success',
        description: 'Agent updated successfully',
      })
    },
  })

  // Delete agent
  const deleteAgent = useMutation({
    mutationFn: agentsApi.delete,
    onMutate: async (id) => {
      await queryClient.cancelQueries({ queryKey: ['agents'] })
      const previousAgents = queryClient.getQueryData(['agents'])
      
      queryClient.setQueryData(['agents'], (old: Agent[] = []) =>
        old.filter(agent => agent.id !== id)
      )

      return { previousAgents }
    },
    onError: (err, id, context) => {
      queryClient.setQueryData(['agents'], context?.previousAgents)
      toast({
        title: 'Error',
        description: 'Failed to delete agent. Please try again.',
        variant: 'destructive',
      })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['agents'] })
      toast({
        title: 'Success',
        description: 'Agent deleted successfully',
      })
    },
  })

  // Duplicate agent
  const duplicateAgent = useMutation({
    mutationFn: agentsApi.duplicate,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['agents'] })
      toast({
        title: 'Success',
        description: 'Agent duplicated successfully',
      })
    },
    onError: () => {
      toast({
        title: 'Error',
        description: 'Failed to duplicate agent. Please try again.',
        variant: 'destructive',
      })
    },
  })

  return {
    createAgent,
    updateAgent,
    deleteAgent,
    duplicateAgent,
  }
}
```

### Dashboard Integration

```typescript
// resources/js/pages/AgentDashboard.tsx (updated with API integration)

import { useAgents, useAgentMutations } from '@/hooks/useAgents'
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog'

export function AgentDashboard() {
  const { data: agents = [], isLoading, error } = useAgents()
  const { createAgent, updateAgent, deleteAgent, duplicateAgent } = useAgentMutations()
  
  const [selectedAgent, setSelectedAgent] = useState<Agent | null>(null)
  const [isCreating, setIsCreating] = useState(false)
  const [agentToDelete, setAgentToDelete] = useState<Agent | null>(null)

  const handleCreate = async (data: Partial<Agent>) => {
    await createAgent.mutateAsync(data)
    setIsCreating(false)
  }

  const handleUpdate = async (data: Partial<Agent>) => {
    if (!selectedAgent) return
    await updateAgent.mutateAsync({ id: selectedAgent.id, data })
    setSelectedAgent(null)
  }

  const handleDeleteClick = (agent: Agent) => {
    setAgentToDelete(agent)
  }

  const handleDeleteConfirm = async () => {
    if (!agentToDelete) return
    await deleteAgent.mutateAsync(agentToDelete.id)
    setAgentToDelete(null)
  }

  const handleDuplicate = async (agent: Agent) => {
    await duplicateAgent.mutateAsync(agent.id)
  }

  return (
    <div className="container mx-auto p-6">
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <div>
          <h1 className="text-3xl font-bold">Agent Management</h1>
          <p className="text-muted-foreground">
            Manage AI agents and their capabilities
          </p>
        </div>
        <Button onClick={() => setIsCreating(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Create New Agent
        </Button>
      </div>

      {/* Grid */}
      {isLoading ? (
        <SkeletonGrid />
      ) : error ? (
        <ErrorState error={error} />
      ) : agents.length === 0 ? (
        <EmptyState onCreateClick={() => setIsCreating(true)} />
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          {agents.map(agent => (
            <AgentMiniCard
              key={agent.id}
              agent={agent}
              onClick={() => setSelectedAgent(agent)}
              onEdit={() => setSelectedAgent(agent)}
              onDelete={() => handleDeleteClick(agent)}
              onDuplicate={() => handleDuplicate(agent)}
            />
          ))}
        </div>
      )}

      {/* Modals */}
      {selectedAgent && (
        <AgentProfileEditor
          agent={selectedAgent}
          isOpen={!!selectedAgent}
          onClose={() => setSelectedAgent(null)}
          onSave={handleUpdate}
          onDelete={() => handleDeleteClick(selectedAgent)}
          onDuplicate={() => handleDuplicate(selectedAgent)}
        />
      )}

      {isCreating && (
        <AgentProfileEditor
          isOpen={isCreating}
          onClose={() => setIsCreating(false)}
          onSave={handleCreate}
        />
      )}

      {/* Delete Confirmation Dialog */}
      <AlertDialog open={!!agentToDelete} onOpenChange={() => setAgentToDelete(null)}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>Delete Agent</AlertDialogTitle>
            <AlertDialogDescription>
              Are you sure you want to delete <strong>{agentToDelete?.name}</strong>?
              This action cannot be undone.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>Cancel</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleDeleteConfirm}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              Delete
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  )
}
```

### Backend Controller (if needed)

```php
// app/Http/Controllers/AgentController.php

namespace App\Http\Controllers;

use App\Models\AgentProfile;
use App\Services\AgentProfileService;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(
        private readonly AgentProfileService $service
    ) {}

    public function index()
    {
        $agents = $this->service->list();
        return response()->json($agents);
    }

    public function store(Request $request)
    {
        $agent = $this->service->create($request->all());
        return response()->json($agent, 201);
    }

    public function show(AgentProfile $agent)
    {
        return response()->json($agent);
    }

    public function update(Request $request, AgentProfile $agent)
    {
        $updated = $this->service->update($agent, $request->all());
        return response()->json($updated);
    }

    public function destroy(AgentProfile $agent)
    {
        $this->service->delete($agent);
        return response()->json(null, 204);
    }

    public function duplicate(AgentProfile $agent)
    {
        $data = $agent->toArray();
        $data['name'] = $agent->name . ' (Copy)';
        unset($data['id'], $data['created_at'], $data['updated_at']);
        
        $duplicate = $this->service->create($data);
        return response()->json($duplicate, 201);
    }

    public function types()
    {
        return response()->json($this->service->availableTypes());
    }

    public function modes()
    {
        return response()->json($this->service->availableModes());
    }

    public function statuses()
    {
        return response()->json($this->service->availableStatuses());
    }
}
```

### Backend Routes

```php
// routes/api.php or routes/internal.php

use App\Http\Controllers\AgentController;

Route::prefix('agents')->group(function () {
    Route::get('/', [AgentController::class, 'index']);
    Route::post('/', [AgentController::class, 'store']);
    Route::get('/{agent}', [AgentController::class, 'show']);
    Route::put('/{agent}', [AgentController::class, 'update']);
    Route::delete('/{agent}', [AgentController::class, 'destroy']);
    Route::post('/{agent}/duplicate', [AgentController::class, 'duplicate']);
    
    Route::get('/meta/types', [AgentController::class, 'types']);
    Route::get('/meta/modes', [AgentController::class, 'modes']);
    Route::get('/meta/statuses', [AgentController::class, 'statuses']);
});
```

## Acceptance Criteria

- [ ] API client methods work for all endpoints
- [ ] `useAgents()` hook fetches agents on mount
- [ ] `useAgentMeta()` hook fetches types/modes/statuses
- [ ] Create operation works end-to-end
- [ ] Update operation works end-to-end
- [ ] Delete shows confirmation dialog
- [ ] Delete operation works end-to-end
- [ ] Duplicate operation works end-to-end
- [ ] Success toasts appear for all operations
- [ ] Error toasts appear on failures
- [ ] Optimistic updates work (UI updates immediately)
- [ ] Rollback works on API errors
- [ ] Loading states show during operations
- [ ] No console errors
- [ ] Backend routes configured properly

## Files to Create/Modify

### New Files
- `resources/js/lib/api/agents.ts` - API client
- `resources/js/hooks/useAgents.ts` - React hooks
- `app/Http/Controllers/AgentController.php` - Backend controller (if doesn't exist)

### Files to Modify
- `resources/js/pages/AgentDashboard.tsx` - Wire up hooks and handlers
- `resources/js/components/agents/AgentProfileEditor.tsx` - Add meta fetching
- `routes/api.php` or `routes/internal.php` - Add agent routes

### Files to Reference
- `app/Services/AgentProfileService.php` - Backend service (already exists)
- Other API clients in `resources/js/lib/api/` for patterns
- Other hooks for patterns

## Testing Checklist

- [ ] Create new agent from dashboard
- [ ] Edit existing agent
- [ ] Delete agent (with confirmation)
- [ ] Duplicate agent (creates copy with "(Copy)" suffix)
- [ ] Success toast shows on create
- [ ] Success toast shows on update
- [ ] Success toast shows on delete
- [ ] Success toast shows on duplicate
- [ ] Error toast shows on API failure
- [ ] Optimistic update shows immediately
- [ ] Rollback works on error
- [ ] Loading spinner shows during operations
- [ ] All backend endpoints return correct data
- [ ] Validation errors from backend display properly

## Notes

- **React Query**: Use for caching and state management
- **Optimistic Updates**: Improve UX by updating UI immediately
- **Error Handling**: Always show user-friendly messages
- **Confirmation**: Only for destructive actions (delete)
- **Duplicate**: Creates copy with modified name and new slug
- **Backend**: Verify AgentController exists or create it
- **Routes**: Confirm routes are in correct file (api.php vs internal.php)
- **CORS**: Ensure API is accessible from frontend

## Dependencies

**Before Starting**:
- ✅ UI-03 completed (editor component exists)
- ✅ Backend AgentProfileService exists
- ✅ React Query installed (or equivalent)
- ✅ Toast component available (shadcn/ui)

**Blocked By**: UI-03

**Blocks**: None (final task in sprint)
