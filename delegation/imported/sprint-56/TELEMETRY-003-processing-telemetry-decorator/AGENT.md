# TELEMETRY-003: Fragment Processing Telemetry Decorator

## Agent Profile: Senior Backend Engineer

### Skills Required
- Laravel job queue system and background processing architecture
- Decorator pattern and aspect-oriented programming concepts
- Fragment processing pipeline understanding and Laravel Pipeline usage
- Performance monitoring and timing instrumentation
- Error handling and exception tracking in distributed systems

### Domain Knowledge
- Fragment Engine processing pipeline (`ProcessFragmentJob`, enrichment actions)
- AI-powered enrichment steps (metadata extraction, tagging, title generation)
- Fragment lifecycle and state management
- Laravel action/service architecture patterns
- Async job processing and queue worker behavior

### Responsibilities
- Design reusable telemetry decorator for pipeline step instrumentation
- Instrument all fragment processing steps with timing and outcome data
- Extract and log generated object keys without storing content
- Implement error context capture for failed processing steps
- Ensure minimal performance overhead for telemetry

### Technical Focus Areas
- **ProcessFragmentJob**: Main job orchestration telemetry
- **Enrichment Actions**: `ExtractJsonMetadata`, `EnrichAssistantMetadata`, `SuggestTags`
- **Processing Actions**: `ParseAtomicFragment`, `GenerateAutoTitle`
- **Pipeline Orchestration**: Laravel Pipeline step instrumentation

### Success Criteria
- All processing steps emit start/completion/failure telemetry
- Step timing and outcome data captured with <1ms overhead per step
- Generated object keys (fragment IDs, tag IDs) logged without content
- Error context preserved for debugging failed enrichment
- Correlation with upstream chat messages maintained
- Reusable decorator pattern for future processing steps