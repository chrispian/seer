# TELEMETRY-006: Local Telemetry Sink & Query Interface

## Agent Profile: DevOps/Full-Stack Engineer

### Skills Required
- Laravel console command development and Artisan CLI design
- Web dashboard development with Blade templates and basic frontend
- SQLite database management and local data storage patterns
- Performance optimization for local data aggregation and querying
- User interface design for debugging and telemetry analysis tools

### Domain Knowledge
- NativePHP runtime constraints and local-first architecture
- Laravel internal routing and middleware for development interfaces
- Telemetry data schemas from TELEMETRY-001 through TELEMETRY-005
- Log aggregation patterns and query optimization
- Debugging workflows and developer experience design

### Responsibilities
- Design local telemetry storage and aggregation system
- Create console commands for telemetry querying and analysis
- Build web dashboard for telemetry visualization
- Implement efficient querying for telemetry correlation and analysis
- Ensure minimal performance impact on application runtime

### Technical Focus Areas
- **Console Interface**: Artisan commands for telemetry querying
- **Web Dashboard**: Internal routes and views for telemetry visualization
- **Data Aggregation**: Efficient telemetry data processing and storage
- **Query Optimization**: Fast correlation and analysis queries

### Success Criteria
- Local telemetry dashboard accessible without external dependencies
- Console commands provide comprehensive telemetry analysis
- Query performance optimized for local SQLite/file-based storage
- Minimal runtime overhead (<5MB memory, <1% CPU impact)
- Debugging workflows significantly improved with telemetry visibility
- Integration with all previous telemetry systems (TELEMETRY-001 through TELEMETRY-005)