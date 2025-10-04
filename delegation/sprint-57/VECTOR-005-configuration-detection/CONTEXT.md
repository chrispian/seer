# Context: Configuration & Feature Detection

## Current State
- Configuration scattered across multiple files
- No centralized feature detection
- Limited feedback when vector extensions unavailable
- Manual configuration required for driver selection

## Target State
- Unified configuration management for vector features
- Automatic feature detection and capability reporting
- Clear UI feedback for missing capabilities
- Zero-config defaults for common deployments
- Health check endpoints for monitoring

## Implementation Focus
- Enhanced config/fragments.php with vector driver settings
- Feature detection service for runtime capability checking
- UI integration for capability status display
- Monitoring endpoints for vector system health
- Environment-specific configuration templates
