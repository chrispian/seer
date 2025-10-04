# MIGRATE-TODO-001: Task Checklist

## Phase 1: Parser Service Development ⏳
- [ ] **1.1 Create TodoTextParser Service** (2 hours)
  - [ ] Create `app/Services/TodoTextParser.php` with basic structure
  - [ ] Implement pattern arrays for dates, priorities, tags
  - [ ] Add main `parse()` method signature
  - [ ] Set up service provider registration

- [ ] **1.2 Implement Pattern Matching Methods** (2-3 hours)
  - [ ] `extractDueDate()`: Handle relative dates (today, tomorrow, next week)
  - [ ] `extractDueDate()`: Handle absolute dates (2024-01-15, weekdays)
  - [ ] `extractPriority()`: Match priority keywords and indicators
  - [ ] `extractTags()`: Find hashtags (#work) and context (@home)
  - [ ] `extractTitle()`: Clean text and extract meaningful title

- [ ] **1.3 Add Business Rule Processing** (1 hour)
  - [ ] Default value assignment logic
  - [ ] Title cleaning and capitalization
  - [ ] Date validation and Carbon conversion
  - [ ] Tag deduplication and normalization

## Phase 2: DSL Step Integration ⏳
- [ ] **2.1 Create TextParseStep Class** (1 hour)
  - [ ] Create `app/Services/Commands/DSL/Steps/TextParseStep.php`
  - [ ] Implement Step interface with execute method
  - [ ] Add TodoTextParser dependency injection
  - [ ] Handle dry run mode

- [ ] **2.2 Register in StepFactory** (15 minutes)
  - [ ] Add `'text.parse' => TextParseStep::class` to StepFactory
  - [ ] Test step creation and registration
  - [ ] Verify step appears in available types

- [ ] **2.3 Add Configuration Validation** (45 minutes)
  - [ ] Validate required `input` parameter
  - [ ] Validate `parser` type selection  
  - [ ] Add error handling for invalid configurations
  - [ ] Add optional `rules` configuration support

## Phase 3: Validation Step Enhancement ⏳
- [ ] **3.1 Enhance ValidateStep** (30 minutes)
  - [ ] Verify existing ValidateStep handles todo data
  - [ ] Test required field validation
  - [ ] Test enum value validation for priorities
  - [ ] Test array validation for tags

- [ ] **3.2 Create Todo Validation Rules** (30 minutes)
  - [ ] Define validation rules for todo data structure
  - [ ] Test validation rule enforcement
  - [ ] Add custom error messages for todo context
  - [ ] Document validation patterns

## Phase 4: Command Migration ⏳
- [ ] **4.1 Update Todo Command YAML** (1 hour)
  - [ ] Replace `ai.generate` step with `text.parse`
  - [ ] Add validation step after parsing
  - [ ] Update model.create with parsed data structure
  - [ ] Update capability requirements

- [ ] **4.2 Remove AI Dependencies** (30 minutes)
  - [ ] Remove `ai.generate` from capabilities
  - [ ] Archive AI prompt files
  - [ ] Update command documentation
  - [ ] Remove AI service dependencies

- [ ] **4.3 Maintain Backward Compatibility** (30 minutes)
  - [ ] Preserve `/todo "text"` interface
  - [ ] Ensure same output fragment structure
  - [ ] Maintain error response patterns
  - [ ] Test existing integrations

## Phase 5: Testing & Validation ⏳
- [ ] **5.1 Unit Testing** (2 hours)
  - [ ] Create `tests/Unit/Services/TodoTextParserTest.php`
  - [ ] Test simple todo parsing: "buy groceries"
  - [ ] Test complex todos: "finish report tomorrow #work urgent"
  - [ ] Test edge cases: empty input, special characters
  - [ ] Test all date parsing patterns
  - [ ] Test all priority extraction patterns
  - [ ] Test tag extraction patterns

- [ ] **5.2 Integration Testing** (1.5 hours)
  - [ ] Create `tests/Feature/Commands/TodoCommandTest.php`
  - [ ] Test end-to-end todo creation flow
  - [ ] Test DSL step integration
  - [ ] Test validation error handling
  - [ ] Test fragment creation with correct data

- [ ] **5.3 Performance Testing** (30 minutes)
  - [ ] Benchmark regex parsing vs AI parsing
  - [ ] Measure memory usage
  - [ ] Test concurrent parsing performance
  - [ ] Validate <10ms target performance

## Phase 6: Documentation & Rollout ⏳
- [ ] **6.1 Update Documentation** (45 minutes)
  - [ ] Document new parser configuration options
  - [ ] Add command usage examples
  - [ ] Create migration guide for developers
  - [ ] Document performance improvements

- [ ] **6.2 Gradual Rollout** (45 minutes)
  - [ ] Implement feature flag for parser selection
  - [ ] Set up monitoring for parser performance
  - [ ] Create rollback plan
  - [ ] Prepare user communication

## Success Validation Checklist ✅
- [ ] **Functional Requirements Met**
  - [ ] Parses 90%+ of common todo patterns correctly
  - [ ] Zero AI dependencies in parsing flow
  - [ ] Graceful fallback for unparseable input
  - [ ] Performance <10ms parsing time
  - [ ] Backward compatibility maintained

- [ ] **Quality Requirements Met**
  - [ ] Test coverage >90%
  - [ ] No performance regression
  - [ ] Error handling for edge cases
  - [ ] Documentation complete

- [ ] **Deployment Requirements Met**
  - [ ] Safe rollout strategy implemented
  - [ ] Monitoring in place
  - [ ] Rollback plan tested
  - [ ] User communication prepared

## Risk Mitigation Checklist ⚠️
- [ ] **Technical Risks Addressed**
  - [ ] Pattern coverage tested with real user examples
  - [ ] Performance benchmarked against current implementation
  - [ ] Parsing accuracy validated manually

- [ ] **Business Risks Addressed**
  - [ ] User experience tested with gradual rollout
  - [ ] Compatibility verified with comprehensive regression tests
  - [ ] Benefits clearly communicated to users

## Dependencies & Blockers 🚫
- **No external dependencies** - can start immediately
- **Parallel development** - can work alongside other MIGRATE-TODO tasks
- **Prerequisites**: Existing DSL framework and database steps (already available)

## Estimated Timeline 📅
- **Total Time**: 13-20 hours
- **Phase 1**: 4-6 hours (Parser development)
- **Phase 2**: 2-3 hours (DSL integration)
- **Phase 3**: 1-2 hours (Validation)
- **Phase 4**: 2-3 hours (Migration)
- **Phase 5**: 3-4 hours (Testing)
- **Phase 6**: 1-2 hours (Documentation)

## Key Files to Create/Modify 📁
### New Files
- `app/Services/TodoTextParser.php`
- `app/Services/Commands/DSL/Steps/TextParseStep.php`
- `tests/Unit/Services/TodoTextParserTest.php`
- `tests/Feature/Commands/TodoCommandTest.php`

### Modified Files
- `app/Services/Commands/DSL/Steps/StepFactory.php`
- `fragments/commands/todo/command.yaml`
- Command documentation files