# SETTINGS-002: Dynamic AI Provider Configuration

## Role
You are a Laravel + React developer implementing dynamic AI provider/model configuration to replace static dropdowns with live provider catalog integration.

## Context
The AI configuration section in `/settings` currently uses static options for providers and models. It should dynamically load metadata from `config/fragments.php`, show capability badges, warn about missing API keys, and enforce project-level limits.

## Current State
- AI settings stored in `profile_settings.ai` JSON column
- Static provider dropdown with hardcoded options
- Model selection doesn't reflect actual provider capabilities
- No validation of API key prerequisites
- Context length and other parameters lack guardrails
- No indication of provider status or availability

## Task Scope
Transform AI configuration into dynamic, metadata-driven system:

### Provider Catalog Integration
- Load provider/model metadata from `config/fragments.php`
- Display provider capabilities and model specifications
- Show availability status and API key requirements
- Surface rate limits, context windows, and feature support

### Enhanced UI Components
- Dynamic provider selector with capability badges
- Model dropdown filtered by selected provider
- Parameter controls with provider-specific limits
- Visual indicators for API key status and connectivity
- Warning messages for missing prerequisites

### Validation & Guardrails
- Validate model selections against provider capabilities
- Enforce project-level context length limits
- Check API key presence and validity
- Prevent invalid configuration combinations
- Guide users toward working configurations

### Status & Feedback
- Real-time provider connectivity status
- API key validation feedback
- Model availability indicators
- Performance and cost guidance
- Configuration health scoring

## Success Criteria
- [ ] Provider options loaded dynamically from catalog
- [ ] Model selections reflect actual provider capabilities
- [ ] API key requirements clearly communicated
- [ ] Parameter limits enforced based on provider specs
- [ ] Configuration validation prevents invalid states
- [ ] Users receive clear guidance for setup issues
- [ ] Provider status visible and actionable

## Technical Constraints
- Must preserve existing `profile_settings.ai` storage format
- Coordinate with existing settings API endpoints
- Follow Laravel configuration patterns
- Use React patterns consistent with other settings
- Ensure backward compatibility with saved configurations