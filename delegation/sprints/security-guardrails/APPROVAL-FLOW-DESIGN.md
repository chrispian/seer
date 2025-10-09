# Approval Flow UX Design

## Overview
Inline approval system that keeps chat clean while allowing users to review and approve high-risk operations. Uses Fragment storage for long-form content with modal previews.

## Core Principles
1. **Inline approvals** - Small actions get inline buttons in chat
2. **Fragment storage** - Long plans/docs stored as fragments with modal preview
3. **Configurable threshold** - Switch between inline/modal based on content size
4. **Natural language fallback** - User can approve via message if they miss button
5. **Clear chat history** - Approved/rejected decisions appear in transcript

## User Experience Flows

### Flow 1: Inline Approval (Short, High-Risk Command)

```
┌─────────────────────────────────────────────────────────┐
│ User                                                     │
│ Delete the old log files in /tmp                        │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Assistant                                                │
│ I found 47 log files in /tmp/logs/ (total: 2.3GB).     │
│ This will execute: `rm -rf /tmp/logs/*.log`             │
│                                                          │
│ ⚠️ Risk: High (60/100)                                  │
│ • Recursive force delete: +40                           │
│ • Shell execution: +20                                  │
│                                                          │
│ [✓ Approve] [✗ Reject]                                  │
└─────────────────────────────────────────────────────────┘
```

**User clicks [✓ Approve]:**
```
┌─────────────────────────────────────────────────────────┐
│ Assistant                                                │
│ I found 47 log files in /tmp/logs/ (total: 2.3GB).     │
│ This will execute: `rm -rf /tmp/logs/*.log`             │
│                                                          │
│ ⚠️ Risk: High (60/100)                                  │
│ • Recursive force delete: +40                           │
│ • Shell execution: +20                                  │
│                                                          │
│ ✓ Approved by user at 2:34 PM                           │
│                                                          │
│ Deleted 47 files (2.3GB freed).                         │
└─────────────────────────────────────────────────────────┘
```

### Flow 2: Modal Preview (Long Plan/Document)

```
┌─────────────────────────────────────────────────────────┐
│ User                                                     │
│ Create a detailed plan to refactor the authentication   │
│ system to use OAuth2 with PKCE flow                     │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Assistant                                                │
│ I've created a comprehensive OAuth2 refactoring plan    │
│ with 8 phases covering security, migration, and testing.│
│                                                          │
│ 📄 View Plan: Authentication OAuth2 Migration           │
│    3,247 words • 12 min read                            │
│                                                          │
│ Summary:                                                 │
│ • Phase 1: Security audit & dependency updates          │
│ • Phase 2: OAuth2 provider setup (PKCE)                 │
│ • Phase 3: Database schema migration                    │
│ • Phase 4-8: Implementation, testing, rollout           │
│                                                          │
│ [View Full Plan] to review and approve                  │
└─────────────────────────────────────────────────────────┘
```

**User clicks [View Full Plan]** → Modal opens:
```
┌──────────────────────────────────────────────────────────┐
│ Authentication OAuth2 Migration Plan              [✕]   │
├──────────────────────────────────────────────────────────┤
│                                                          │
│ [Full markdown document with 3,247 words displayed]     │
│                                                          │
│ ... (scrollable content) ...                            │
│                                                          │
├──────────────────────────────────────────────────────────┤
│                              [✓ Approve] [✗ Reject]     │
└──────────────────────────────────────────────────────────┘
```

**User clicks [✓ Approve]** → Modal closes, chat updates:
```
┌─────────────────────────────────────────────────────────┐
│ Assistant                                                │
│ I've created a comprehensive OAuth2 refactoring plan    │
│ with 8 phases covering security, migration, and testing.│
│                                                          │
│ 📄 Authentication OAuth2 Migration (view)               │
│    3,247 words • 12 min read                            │
│                                                          │
│ ✓ Plan approved by user at 2:35 PM                      │
│                                                          │
│ I'll start with Phase 1: Security Audit. Should I       │
│ create tasks for each phase?                            │
└─────────────────────────────────────────────────────────┘
```

### Flow 3: Natural Language Approval Fallback

```
┌─────────────────────────────────────────────────────────┐
│ Assistant                                                │
│ I'll need to install these packages:                    │
│ • axios@1.6.0                                           │
│ • lodash@4.17.21                                        │
│                                                          │
│ ⚠️ Risk: Medium (35/100)                                │
│ [✓ Approve] [✗ Reject]                                  │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ User                                                     │
│ Yes, go ahead and install them                          │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Assistant                                                │
│ ✓ Interpreting as approval                              │
│                                                          │
│ Installing packages...                                  │
│ ✓ axios@1.6.0 installed                                 │
│ ✓ lodash@4.17.21 installed                              │
└─────────────────────────────────────────────────────────┘
```

### Flow 4: Rejection

```
┌─────────────────────────────────────────────────────────┐
│ Assistant                                                │
│ This will reset the database and delete all user data.  │
│                                                          │
│ ⚠️ Risk: Critical (95/100)                              │
│ [✓ Approve] [✗ Reject]                                  │
└─────────────────────────────────────────────────────────┘

User clicks [✗ Reject]

┌─────────────────────────────────────────────────────────┐
│ Assistant                                                │
│ This will reset the database and delete all user data.  │
│                                                          │
│ ⚠️ Risk: Critical (95/100)                              │
│                                                          │
│ ✗ Rejected by user at 2:36 PM                           │
│                                                          │
│ Understood. I won't proceed with the database reset.    │
│ Would you like to create a backup first instead?        │
└─────────────────────────────────────────────────────────┘
```

### Flow 5: Refinement (No Approval Yet)

```
┌─────────────────────────────────────────────────────────┐
│ Assistant                                                │
│ I'll create a new user account with admin privileges.   │
│                                                          │
│ [✓ Approve] [✗ Reject]                                  │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ User                                                     │
│ Actually, make it a read-only user instead              │
└─────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────┐
│ Assistant                                                │
│ Got it! I'll create a read-only user instead.           │
│                                                          │
│ Username: readonly-user                                 │
│ Permissions: read-only access to all resources          │
│                                                          │
│ [✓ Approve] [✗ Reject]                                  │
└─────────────────────────────────────────────────────────┘
```

## Configuration

### Content Size Thresholds
```php
// config/security/approval.php
return [
    'inline_approval' => [
        'max_characters' => 500,  // Switch to modal above this
        'max_words' => 100,       // Alternative threshold
        'max_lines' => 15,        // Line-based threshold
    ],
    
    'modal_preview' => [
        'enabled' => true,
        'preview_words' => 50,    // Summary length in chat
        'show_stats' => true,     // Word count, read time
    ],
    
    'natural_language_approval' => [
        'enabled' => true,
        'confidence_threshold' => 0.8,  // AI confidence for approval detection
        'approval_keywords' => ['yes', 'approve', 'go ahead', 'do it', 'proceed'],
        'rejection_keywords' => ['no', 'reject', 'cancel', 'stop', 'don\'t'],
    ],
];
```

## Technical Implementation

### Backend: Approval Request Model

```php
// app/Models/ApprovalRequest.php
class ApprovalRequest extends Model
{
    protected $fillable = [
        'operation_type',      // 'command', 'file_operation', 'network', 'tool_call'
        'operation_summary',   // Short description for chat
        'operation_details',   // Full details (JSON)
        'risk_score',          // 0-100
        'risk_level',          // low/medium/high/critical
        'risk_factors',        // JSON array of contributing factors
        'dry_run_result',      // Simulation results (JSON)
        'fragment_id',         // If long-form content stored as fragment
        'status',              // pending/approved/rejected/timeout
        'approved_by_user_id', // Who approved
        'approved_at',         // When approved
        'approval_method',     // 'button_click' or 'natural_language'
        'user_message',        // Natural language approval/rejection
        'conversation_id',     // Link to chat session
        'message_id',          // Link to chat message
        'timeout_at',          // Auto-reject after 5 minutes
    ];
    
    protected $casts = [
        'operation_details' => 'array',
        'risk_factors' => 'array',
        'dry_run_result' => 'array',
        'approved_at' => 'datetime',
        'timeout_at' => 'datetime',
    ];
}
```

### Backend: Approval Manager Service

```php
// app/Services/Security/ApprovalManager.php
class ApprovalManager
{
    public function createApprovalRequest(array $operation): ApprovalRequest
    {
        // 1. Run dry-run simulation
        $dryRun = app(DryRunSimulator::class)->simulate($operation);
        
        // 2. Calculate risk
        $risk = app(RiskScorer::class)->score($operation);
        
        // 3. Determine if needs approval
        if ($risk['action'] === 'auto_approve') {
            return null; // No approval needed
        }
        
        // 4. Check content size for inline vs modal
        $contentSize = $this->calculateContentSize($operation);
        $fragmentId = null;
        
        if ($contentSize['use_modal']) {
            // Store as fragment
            $fragment = Fragment::create([
                'type' => 'plan',
                'title' => $operation['summary'],
                'message' => $operation['full_content'],
                'tags' => ['approval-request', 'security'],
                'metadata' => [
                    'risk_score' => $risk['score'],
                    'operation_type' => $operation['type'],
                ],
            ]);
            $fragmentId = $fragment->id;
        }
        
        // 5. Create approval request
        return ApprovalRequest::create([
            'operation_type' => $operation['type'],
            'operation_summary' => $operation['summary'],
            'operation_details' => $operation,
            'risk_score' => $risk['score'],
            'risk_level' => $risk['level'],
            'risk_factors' => $risk['factors'],
            'dry_run_result' => $dryRun,
            'fragment_id' => $fragmentId,
            'status' => 'pending',
            'timeout_at' => now()->addMinutes(5),
        ]);
    }
    
    public function approveRequest(ApprovalRequest $request, int $userId, string $method = 'button_click', ?string $message = null): void
    {
        $request->update([
            'status' => 'approved',
            'approved_by_user_id' => $userId,
            'approved_at' => now(),
            'approval_method' => $method,
            'user_message' => $message,
        ]);
        
        // Log to audit trail
        activity()
            ->causedBy($userId)
            ->performedOn($request)
            ->event('approval_granted')
            ->log("User approved {$request->operation_type}: {$request->operation_summary}");
    }
    
    public function rejectRequest(ApprovalRequest $request, int $userId, string $method = 'button_click', ?string $message = null): void
    {
        $request->update([
            'status' => 'rejected',
            'approved_by_user_id' => $userId,
            'approved_at' => now(),
            'approval_method' => $method,
            'user_message' => $message,
        ]);
        
        activity()
            ->causedBy($userId)
            ->performedOn($request)
            ->event('approval_denied')
            ->log("User rejected {$request->operation_type}: {$request->operation_summary}");
    }
    
    public function detectApprovalInMessage(string $message): ?string
    {
        // Use AI or keyword matching to detect approval intent
        $approvalKeywords = config('security.approval.natural_language_approval.approval_keywords');
        $rejectionKeywords = config('security.approval.natural_language_approval.rejection_keywords');
        
        $messageLower = strtolower($message);
        
        foreach ($approvalKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return 'approve';
            }
        }
        
        foreach ($rejectionKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return 'reject';
            }
        }
        
        return null; // Ambiguous
    }
    
    private function calculateContentSize(array $operation): array
    {
        $content = $operation['full_content'] ?? '';
        $wordCount = str_word_count($content);
        $charCount = strlen($content);
        $lineCount = substr_count($content, "\n") + 1;
        
        $config = config('security.approval.inline_approval');
        
        $useModal = (
            $wordCount > $config['max_words'] ||
            $charCount > $config['max_characters'] ||
            $lineCount > $config['max_lines']
        );
        
        return [
            'use_modal' => $useModal,
            'word_count' => $wordCount,
            'char_count' => $charCount,
            'line_count' => $lineCount,
            'read_time_minutes' => ceil($wordCount / 200), // Avg reading speed
        ];
    }
}
```

### Frontend: Approval Button Component

```typescript
// resources/js/components/ApprovalButton.tsx
import React from 'react'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { CheckIcon, XIcon, AlertTriangleIcon } from 'lucide-react'

interface ApprovalButtonProps {
  requestId: string
  riskScore: number
  riskLevel: 'low' | 'medium' | 'high' | 'critical'
  riskFactors: string[]
  onApprove: () => void
  onReject: () => void
  isApproved?: boolean
  isRejected?: boolean
  approvedAt?: string
  rejectedAt?: string
}

export function ApprovalButton({
  requestId,
  riskScore,
  riskLevel,
  riskFactors,
  onApprove,
  onReject,
  isApproved,
  isRejected,
  approvedAt,
  rejectedAt,
}: ApprovalButtonProps) {
  const [isProcessing, setProcessing] = React.useState(false)
  
  const riskColors = {
    low: 'bg-green-500/10 text-green-700 border-green-500/20',
    medium: 'bg-yellow-500/10 text-yellow-700 border-yellow-500/20',
    high: 'bg-orange-500/10 text-orange-700 border-orange-500/20',
    critical: 'bg-red-500/10 text-red-700 border-red-500/20',
  }
  
  // Already decided - show status
  if (isApproved) {
    return (
      <div className="flex items-center gap-2 text-sm text-green-700 bg-green-50 px-3 py-2 rounded border border-green-200">
        <CheckIcon className="w-4 h-4" />
        <span>Approved by user at {new Date(approvedAt).toLocaleTimeString()}</span>
      </div>
    )
  }
  
  if (isRejected) {
    return (
      <div className="flex items-center gap-2 text-sm text-red-700 bg-red-50 px-3 py-2 rounded border border-red-200">
        <XIcon className="w-4 h-4" />
        <span>Rejected by user at {new Date(rejectedAt).toLocaleTimeString()}</span>
      </div>
    )
  }
  
  // Pending approval
  return (
    <div className="space-y-2 py-2">
      {/* Risk Badge */}
      <div className={`inline-flex items-center gap-2 px-3 py-1.5 rounded border text-sm ${riskColors[riskLevel]}`}>
        <AlertTriangleIcon className="w-4 h-4" />
        <span className="font-medium">Risk: {riskLevel.charAt(0).toUpperCase() + riskLevel.slice(1)} ({riskScore}/100)</span>
      </div>
      
      {/* Risk Factors */}
      {riskFactors.length > 0 && (
        <ul className="text-sm text-muted-foreground space-y-0.5 ml-6">
          {riskFactors.slice(0, 3).map((factor, i) => (
            <li key={i}>• {factor}</li>
          ))}
        </ul>
      )}
      
      {/* Action Buttons */}
      <div className="flex gap-2">
        <Button
          size="sm"
          variant="default"
          disabled={isProcessing}
          onClick={async () => {
            setProcessing(true)
            await onApprove()
            setProcessing(false)
          }}
        >
          <CheckIcon className="w-4 h-4 mr-1" />
          Approve
        </Button>
        
        <Button
          size="sm"
          variant="outline"
          disabled={isProcessing}
          onClick={async () => {
            setProcessing(true)
            await onReject()
            setProcessing(false)
          }}
        >
          <XIcon className="w-4 h-4 mr-1" />
          Reject
        </Button>
      </div>
    </div>
  )
}
```

### Frontend: Fragment Preview Modal

```typescript
// resources/js/components/FragmentPreviewModal.tsx
import React from 'react'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import ReactMarkdown from 'react-markdown'
import { CheckIcon, XIcon, FileTextIcon } from 'lucide-react'

interface FragmentPreviewModalProps {
  isOpen: boolean
  onClose: () => void
  fragmentId: string
  title: string
  content: string
  wordCount: number
  readTimeMinutes: number
  riskScore?: number
  riskLevel?: string
  onApprove?: () => void
  onReject?: () => void
}

export function FragmentPreviewModal({
  isOpen,
  onClose,
  fragmentId,
  title,
  content,
  wordCount,
  readTimeMinutes,
  riskScore,
  riskLevel,
  onApprove,
  onReject,
}: FragmentPreviewModalProps) {
  const [isProcessing, setProcessing] = React.useState(false)
  
  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-4xl max-h-[90vh] overflow-hidden flex flex-col">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <FileTextIcon className="w-5 h-5" />
            {title}
          </DialogTitle>
          <div className="flex gap-2 text-sm text-muted-foreground">
            <Badge variant="outline">{wordCount} words</Badge>
            <Badge variant="outline">{readTimeMinutes} min read</Badge>
            {riskScore && (
              <Badge variant={riskLevel === 'high' || riskLevel === 'critical' ? 'destructive' : 'secondary'}>
                Risk: {riskScore}/100
              </Badge>
            )}
          </div>
        </DialogHeader>
        
        <div className="flex-1 overflow-y-auto prose prose-sm dark:prose-invert max-w-none p-4">
          <ReactMarkdown>{content}</ReactMarkdown>
        </div>
        
        {(onApprove || onReject) && (
          <DialogFooter>
            {onReject && (
              <Button
                variant="outline"
                disabled={isProcessing}
                onClick={async () => {
                  setProcessing(true)
                  await onReject()
                  setProcessing(false)
                  onClose()
                }}
              >
                <XIcon className="w-4 h-4 mr-2" />
                Reject
              </Button>
            )}
            {onApprove && (
              <Button
                disabled={isProcessing}
                onClick={async () => {
                  setProcessing(true)
                  await onApprove()
                  setProcessing(false)
                  onClose()
                }}
              >
                <CheckIcon className="w-4 h-4 mr-2" />
                Approve
              </Button>
            )}
          </DialogFooter>
        )}
      </DialogContent>
    </Dialog>
  )
}
```

### Chat Message Format with Approval

```typescript
// Extended ChatMessage interface
export interface ChatMessage {
  id: string
  role: 'user' | 'assistant'
  md: string
  isBookmarked?: boolean
  messageId?: string
  fragmentId?: string
  
  // Approval-specific fields
  approvalRequest?: {
    id: string
    operationType: string
    riskScore: number
    riskLevel: string
    riskFactors: string[]
    status: 'pending' | 'approved' | 'rejected'
    approvedAt?: string
    rejectedAt?: string
    
    // For modal preview
    useModal: boolean
    fragmentId?: string
    fragmentTitle?: string
    wordCount?: number
    readTimeMinutes?: number
  }
}
```

### API Endpoints

```php
// routes/api.php
Route::prefix('approvals')->middleware('auth:sanctum')->group(function () {
    Route::post('/', [ApprovalController::class, 'create']);
    Route::post('/{id}/approve', [ApprovalController::class, 'approve']);
    Route::post('/{id}/reject', [ApprovalController::class, 'reject']);
    Route::get('/{id}', [ApprovalController::class, 'show']);
    Route::get('/pending', [ApprovalController::class, 'pending']);
});

// app/Http/Controllers/Api/ApprovalController.php
class ApprovalController extends Controller
{
    public function approve(Request $request, string $id): JsonResponse
    {
        $approvalRequest = ApprovalRequest::findOrFail($id);
        
        if ($approvalRequest->status !== 'pending') {
            return response()->json(['error' => 'Request already processed'], 400);
        }
        
        $manager = app(ApprovalManager::class);
        $manager->approveRequest(
            $approvalRequest,
            auth()->id(),
            'button_click'
        );
        
        return response()->json([
            'success' => true,
            'approval' => $approvalRequest->fresh(),
        ]);
    }
    
    public function reject(Request $request, string $id): JsonResponse
    {
        $approvalRequest = ApprovalRequest::findOrFail($id);
        
        if ($approvalRequest->status !== 'pending') {
            return response()->json(['error' => 'Request already processed'], 400);
        }
        
        $manager = app(ApprovalManager::class);
        $manager->rejectRequest(
            $approvalRequest,
            auth()->id(),
            'button_click',
            $request->input('reason')
        );
        
        return response()->json([
            'success' => true,
            'approval' => $approvalRequest->fresh(),
        ]);
    }
}
```

## Advantages of This Design

### 1. Clean Chat Interface
- No giant walls of text in chat
- Buttons disappear after decision
- Clear approval/rejection history

### 2. Fragment Storage Benefits
- Long documents stored properly in database
- Searchable, taggable, referenceable
- Can link to fragments later ("view that plan we approved yesterday")
- Consistent with existing system architecture

### 3. Natural Language Fallback
- User doesn't need to find button
- Can respond naturally: "yes do it"
- Agent confirms interpretation before proceeding

### 4. Flexible Configuration
- Admins can tune inline vs modal threshold
- Can disable natural language approval if desired
- Risk thresholds configurable per operation type

### 5. Full Audit Trail
- Every approval/rejection logged
- Method tracked (button vs NL)
- Timestamps and user attribution
- Integrates with existing audit system

## Next Steps

1. **Create Migration** - `approval_requests` table
2. **Create Models** - `ApprovalRequest` model
3. **Create Services** - `ApprovalManager` service
4. **Create Config** - `config/security/approval.php`
5. **Create Frontend Components** - `ApprovalButton`, `FragmentPreviewModal`
6. **Integrate with Chat** - Extend `ChatMessage` interface, update `ChatApiController`
7. **Test Flows** - Manual testing of all 5 flows
