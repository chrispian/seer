# TELEMETRY-001: Request Correlation Middleware - Implementation Plan

## Estimated Time: 6 hours

## Phase 1: Middleware Implementation (2 hours)

### 1.1 Create Correlation Middleware
**File**: `app/Http/Middleware/InjectCorrelationId.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class InjectCorrelationId
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate unique correlation ID
        $correlationId = (string) Str::uuid();
        
        // Store in request attributes for access throughout request lifecycle
        $request->attributes->set('correlation_id', $correlationId);
        
        // Add to log context so all subsequent logs include it automatically
        Log::withContext([
            'correlation_id' => $correlationId,
            'user_id' => 'local-default' // Static for single-user NativePHP
        ]);
        
        return $next($request);
    }
}
```

### 1.2 Register Middleware in HTTP Kernel
**File**: `app/Http/Kernel.php`

```php
// Add to $middleware array (global middleware)
protected $middleware = [
    // ... existing middleware
    \App\Http\Middleware\InjectCorrelationId::class,
];
```

## Phase 2: Context Propagation Helpers (2 hours)

### 2.1 Create Correlation Helper Service
**File**: `app/Services/Telemetry/CorrelationContext.php`

```php
<?php

namespace App\Services\Telemetry;

class CorrelationContext
{
    public static function get(): ?string
    {
        return request()?->attributes?->get('correlation_id');
    }
    
    public static function getOrGenerate(): string
    {
        return self::get() ?? (string) \Illuminate\Support\Str::uuid();
    }
    
    public static function forJob(): array
    {
        return [
            'correlation_id' => self::get(),
            'user_id' => 'local-default'
        ];
    }
}
```

### 2.2 Enhance Job Base Class for Correlation
**File**: `app/Jobs/CorrelatedJob.php` (new trait/base class)

```php
<?php

namespace App\Jobs;

use App\Services\Telemetry\CorrelationContext;
use Illuminate\Support\Facades\Log;

trait HasCorrelationContext
{
    protected array $correlationContext = [];
    
    public function withCorrelation(): self
    {
        $this->correlationContext = CorrelationContext::forJob();
        return $this;
    }
    
    protected function setupCorrelationLogging(): void
    {
        if (!empty($this->correlationContext)) {
            Log::withContext($this->correlationContext);
        }
    }
}
```

## Phase 3: Integration with Existing Jobs (1.5 hours)

### 3.1 Update ProcessFragmentJob
**File**: `app/Jobs/ProcessFragmentJob.php`

```php
// Add trait usage
use App\Jobs\HasCorrelationContext;

class ProcessFragmentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, HasCorrelationContext;
    
    public function handle()
    {
        $this->setupCorrelationLogging();
        
        // existing logic...
    }
}
```

### 3.2 Update Job Dispatching
Update controllers to dispatch jobs with correlation:

```php
// In ChatApiController, CommandController, etc.
ProcessFragmentJob::dispatch($fragment)->withCorrelation();
```

## Phase 4: Testing & Validation (0.5 hours)

### 4.1 Unit Tests
**File**: `tests/Unit/Middleware/InjectCorrelationIdTest.php`

```php
<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\InjectCorrelationId;
use Illuminate\Http\Request;
use Tests\TestCase;

class InjectCorrelationIdTest extends TestCase
{
    public function test_correlation_id_is_injected()
    {
        $middleware = new InjectCorrelationId();
        $request = new Request();
        
        $response = $middleware->handle($request, function ($req) {
            $this->assertNotNull($req->attributes->get('correlation_id'));
            return response('ok');
        });
        
        $this->assertEquals('ok', $response->getContent());
    }
    
    public function test_correlation_id_is_uuid_format()
    {
        $middleware = new InjectCorrelationId();
        $request = new Request();
        
        $middleware->handle($request, function ($req) {
            $correlationId = $req->attributes->get('correlation_id');
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $correlationId
            );
            return response('ok');
        });
    }
}
```

### 4.2 Integration Tests
**File**: `tests/Feature/Telemetry/CorrelationTrackingTest.php`

```php
<?php

namespace Tests\Feature\Telemetry;

use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CorrelationTrackingTest extends TestCase
{
    public function test_chat_api_includes_correlation_id()
    {
        Log::spy();
        
        $response = $this->postJson('/api/chat/send', [
            'content' => 'test message'
        ]);
        
        $response->assertOk();
        
        Log::shouldHaveReceived('withContext')
            ->with(\Mockery::on(function ($context) {
                return isset($context['correlation_id']) && 
                       isset($context['user_id']) &&
                       $context['user_id'] === 'local-default';
            }));
    }
}
```

## Implementation Checklist

- [ ] Create `InjectCorrelationId` middleware
- [ ] Register middleware in HTTP kernel  
- [ ] Create `CorrelationContext` helper service
- [ ] Create `HasCorrelationContext` trait for jobs
- [ ] Update `ProcessFragmentJob` with correlation support
- [ ] Update job dispatching in controllers
- [ ] Write unit tests for middleware
- [ ] Write integration tests for correlation tracking
- [ ] Test with existing chat and command flows
- [ ] Verify correlation IDs appear in log files

## Risk Mitigation

### Performance Concerns
- UUID generation is ~0.1ms, negligible overhead
- Log context modification is O(1) operation
- No database or external system calls

### Compatibility Issues  
- Test with existing middleware stack
- Ensure no conflicts with authentication middleware
- Validate queue worker compatibility

### Privacy Compliance
- Only store correlation UUID, no content
- Static user_id for single-user environment
- No PII captured in correlation context

## Success Metrics

- Correlation IDs present in 100% of request logs
- Zero performance degradation (<1ms overhead)
- Jobs inherit correlation context from dispatching request
- Integration tests pass for chat, command, and fragment flows