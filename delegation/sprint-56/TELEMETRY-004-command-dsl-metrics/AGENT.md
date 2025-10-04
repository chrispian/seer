# TELEMETRY-004: Command & DSL Execution Metrics

## Agent Profile: Senior Backend Engineer

### Skills Required
- Laravel command controller architecture and routing
- DSL (Domain Specific Language) design and execution patterns
- Command pattern implementation and step-based processing
- Performance monitoring and execution timing measurement
- Error handling and failure mode analysis

### Domain Knowledge
- Fragments Engine command system (`CommandController`, `CommandRunner`)
- DSL step architecture and execution pipeline
- Tool integration and invocation patterns
- Command scheduling and background execution
- Fragment manipulation commands and database operations

### Responsibilities
- Instrument command execution controller with structured metrics
- Enhance DSL runner with step-level performance tracking
- Add telemetry to mutating DSL steps (database operations, fragment updates)
- Implement command success/failure rate tracking
- Design dry-run execution telemetry for testing scenarios

### Technical Focus Areas
- **CommandController**: Command request handling and execution orchestration
- **CommandRunner**: DSL execution pipeline and step coordination
- **DSL Steps**: Individual step instrumentation (DatabaseUpdateStep, FragmentUpdateStep, etc.)
- **Command Scheduling**: Scheduled command execution telemetry

### Success Criteria
- All command executions logged with structured metadata
- DSL step performance tracked individually
- Command success/failure rates measurable
- Dry-run executions properly instrumented
- Integration with correlation middleware and tool invocation tracking
- <3ms telemetry overhead per command execution