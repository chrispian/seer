# AUDIT-AI-001: AI-Dependent Command Catalog

## Agent Profile
**Type**: DevRel Specialist  
**Specialization**: System Analysis, Documentation, Command Architecture

## Task Overview
Catalog all commands using `ai.generate` steps and analyze their AI dependencies to plan deterministic migration strategies.

## Context
Based on codebase analysis, several commands currently use AI generation:
- `/todo` - Uses AI for text parsing
- `/news-digest` - Uses AI for content generation  
- `/todo-original` - Legacy AI-based todo parsing
- Other commands in delegation packs

## Technical Requirements

### **Audit Scope**
- **Current Commands**: All YAML commands in `fragments/commands/`
- **Legacy Commands**: Commands in `delegation/` directories
- **Step Analysis**: Identify all `ai.generate` usage patterns
- **Dependency Mapping**: Map AI dependencies to business logic

### **Catalog Structure**
For each AI-dependent command, document:
- Command name and location
- AI step configuration and prompts
- Input/output data structures
- Business logic complexity
- Migration difficulty assessment
- Deterministic alternatives

## Implementation Plan

### **Phase 1: Command Discovery**
Systematically scan codebase for AI dependencies:
```bash
# Find all commands using ai.generate
grep -r "ai.generate" fragments/commands/
grep -r "ai.generate" delegation/
grep -r "AiGenerateStep" app/
```

### **Phase 2: Command Analysis**
For each identified command:
1. **Document Current Behavior**
   - AI prompt content and structure
   - Input data requirements
   - Expected output format
   - Error handling patterns

2. **Assess Complexity**
   - Simple text transformation (low complexity)
   - Business rule application (medium complexity)
   - Creative content generation (high complexity)
   - Multi-step reasoning (very high complexity)

3. **Identify Migration Path**
   - Direct regex/rule replacement
   - Hybrid AI/deterministic approach
   - Pure deterministic alternative
   - Keep AI but add deterministic fallback

### **Phase 3: Create Migration Matrix**
Priority matrix based on:
- **Usage Frequency**: How often is the command used?
- **Migration Difficulty**: How complex is the AI logic?
- **Business Impact**: How critical is deterministic behavior?
- **User Experience**: Will migration improve/maintain UX?

## Audit Findings Template

### **Command: `/todo`**
- **Location**: `fragments/commands/todo/command.yaml`
- **AI Usage**: Text parsing - "buy groceries tomorrow" → structured data
- **Prompt**: `prompts/parse.md` - structured JSON output from natural language
- **Complexity**: Medium (date parsing, priority extraction, title cleanup)
- **Migration Path**: Regex + business rules (MIGRATE-TODO-001)
- **Priority**: High (foundational command, high usage)

### **Command: `/news-digest`**
- **Location**: `fragments/commands/news-digest/command.yaml`
- **AI Usage**: Content summarization and formatting
- **Prompt**: `prompts/process.md` - generate digest from multiple sources
- **Complexity**: High (content analysis, summarization, formatting)
- **Migration Path**: Template-based + RSS processing + keyword extraction
- **Priority**: Medium (specialized use case, medium usage)

### **Command: `/todo-original`**
- **Location**: `fragments/commands/todo-original/command.yaml`
- **AI Usage**: Legacy parsing approach
- **Prompt**: Similar to current todo but different structure
- **Complexity**: Medium (similar to current todo)
- **Migration Path**: Deprecate in favor of new deterministic todo
- **Priority**: Low (legacy command, low usage)

## Deterministic Migration Categories

### **Category 1: Direct Replacement** (High Priority)
Commands where AI performs structured data extraction:
- Text parsing with clear patterns
- Form data processing
- Simple transformations
- **Migration**: Regex + validation rules

### **Category 2: Hybrid Approach** (Medium Priority)
Commands with some AI creativity but core deterministic logic:
- Content formatting with templates
- Data enrichment with rules
- Conditional processing
- **Migration**: Templates + rules + optional AI enhancement

### **Category 3: AI-Optional** (Medium Priority)
Commands that benefit from AI but can function without:
- Content suggestions
- Smart defaults
- Enhancement features
- **Migration**: Deterministic core + AI enhancement mode

### **Category 4: AI-Required** (Low Priority)
Commands fundamentally requiring AI capabilities:
- Creative content generation
- Complex reasoning
- Natural language understanding
- **Migration**: Keep AI but add performance/reliability improvements

## Catalog Output Format

### **Detailed Analysis Report**
```markdown
# AI-Dependent Command Audit Report

## Executive Summary
- Total commands analyzed: X
- AI-dependent commands: Y
- Migration candidates: Z
- Direct replacement possible: A
- Hybrid approach recommended: B
- AI-required commands: C

## Command-by-Command Analysis
[Detailed breakdown for each command]

## Migration Roadmap
[Priority matrix and timeline recommendations]

## Resource Requirements
[Development effort estimates]
```

### **Migration Planning Data**
```json
{
  "commands": [
    {
      "name": "todo",
      "location": "fragments/commands/todo/command.yaml",
      "aiSteps": ["parse_text"],
      "complexity": "medium",
      "migrationPath": "regex_rules",
      "priority": "high",
      "effort": "13-20 hours",
      "dependencies": ["text.parse", "validate"]
    }
  ],
  "summary": {
    "totalCommands": 15,
    "aiDependent": 8,
    "migrationCandidates": 6,
    "effortEstimate": "80-120 hours"
  }
}
```

## Success Criteria
- [ ] Complete inventory of all AI-dependent commands
- [ ] Migration difficulty assessment for each command
- [ ] Priority matrix for migration planning
- [ ] Effort estimates for development planning
- [ ] Clear categorization by migration approach
- [ ] Actionable recommendations for each command

## Deliverables
1. **Comprehensive audit report** with command analysis
2. **Migration planning matrix** with priorities and timelines
3. **Resource estimation** for development planning
4. **Recommendation document** for stakeholder decision-making
5. **Command dependency graph** showing relationships

## Tools and Methods
- **Automated scanning**: Scripts to find AI usage patterns
- **Manual analysis**: Review prompts and business logic
- **Usage analytics**: Identify high-priority commands by usage
- **Stakeholder input**: Gather requirements for deterministic behavior
- **Technical assessment**: Evaluate migration feasibility