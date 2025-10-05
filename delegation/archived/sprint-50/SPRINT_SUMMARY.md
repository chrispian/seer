# Sprint 50: DSL Deterministic Foundation

## Overview
Transform DSL custom commands from AI-dependent to deterministic execution patterns, enabling reliable command flows that can be authored via UI builders.

## Sprint Goals
1. **Deterministic `/todo` Migration**: Replace AI parsing with structured DSL validation
2. **AI-Dependent Command Audit**: Catalog and plan alternatives for AI-heavy commands
3. **Utility Step Foundation**: Add data manipulation steps for complex flows
4. **Capability Metadata**: Enable filtering and policy enforcement

## Task Packs

### **MIGRATE-TODO**: Deterministic Todo Command Migration
**Objective**: Replace AI-dependent parsing in `/todo` with structured input validation and regex/rule-based parsing.

### **AUDIT-AI**: AI-Dependent Command Analysis
**Objective**: Identify commands using `ai.generate` and plan deterministic alternatives or optional fallbacks.

## Success Metrics
- **Deterministic Coverage**: `/todo` command executes without AI dependency
- **Utility Foundation**: Data manipulation steps available for complex flows
- **Command Audit**: Complete inventory of AI dependencies with migration paths
- **Registry Enhancement**: Capability flags enable policy enforcement

## Dependencies
- Existing DSL framework (`CommandRunner`, `StepFactory`)
- Database steps foundation (`model.*` operations)
- Fragment system integration
- Command registry infrastructure

## Sprint Deliverables
1. Deterministic `/todo` command implementation
2. Utility step library for data manipulation
3. AI-dependent command audit report
4. Enhanced command registry with capability metadata
5. Foundation for visual flow builder compatibility