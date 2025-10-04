# TELEMETRY-001: Request Correlation Middleware

## Agent Profile: Senior Backend Engineer

### Skills Required
- Laravel middleware architecture and HTTP kernel configuration
- Request lifecycle management and context propagation
- PHP logging frameworks (Monolog) and Laravel Log facade
- UUID generation and request attribute handling
- Middleware ordering and dependency management

### Domain Knowledge
- Understanding of Laravel request pipeline and middleware stack
- Experience with distributed tracing concepts and correlation IDs
- Knowledge of logging best practices for single-user applications
- Familiarity with NativePHP runtime constraints and requirements

### Responsibilities
- Design and implement correlation ID middleware for request tracking
- Ensure correlation IDs propagate through entire request lifecycle
- Integrate with existing Laravel logging infrastructure
- Test middleware with existing chat and command pipelines
- Document correlation ID usage patterns for downstream telemetry

### Success Criteria
- Every HTTP request gets unique correlation UUID
- All logs within request scope include correlation ID in context
- Middleware registers properly in HTTP kernel without conflicts
- Zero performance impact on existing request processing
- Integration tests validate correlation ID propagation