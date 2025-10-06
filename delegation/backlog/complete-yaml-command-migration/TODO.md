# YAML Command Migration TODO

## Investigation Phase ✋

### System Architecture Analysis
- [ ] Audit `app/Services/CommandRegistry.php` - document all hardcoded commands
- [ ] Inventory `fragments/commands/` directory - catalog all YAML commands  
- [ ] Compare hardcoded vs YAML command lists - identify overlaps and gaps
- [ ] Investigate `CommandPackLoader` class - understand YAML loading mechanism
- [ ] Trace slash command routing - from frontend to backend execution
- [ ] Debug why YAML commands weren't accessible when PHP commands were disabled

### Feature Parity Assessment  
- [ ] Test `/help` command - compare PHP vs YAML behavior
- [ ] Test `/clear` command - verify functionality match
- [ ] Test `/search` command - validate search capabilities
- [ ] Test `/bookmark` command - ensure bookmark creation works
- [ ] Test `/join` command - verify channel joining works
- [ ] Test `/session` command - validate session management
- [ ] Document any behavioral differences found
- [ ] Identify missing features in YAML versions

### Current State Documentation
- [ ] Create command comparison matrix (PHP vs YAML)
- [ ] Document command aliases and shortcuts that need preservation
- [ ] Map command dependencies and interactions
- [ ] Identify high-priority commands for migration order

## Implementation Phase 🔨

### Missing YAML Commands
- [ ] Implement YAML version of `/recall` if missing features
- [ ] Implement YAML version of `/todo` if missing features  
- [ ] Implement YAML version of `/vault` if missing features
- [ ] Implement YAML version of `/project` if missing features
- [ ] Implement YAML version of `/context` if missing features
- [ ] Implement YAML version of `/compose` if missing features
- [ ] Implement YAML version of `/inbox` if missing features
- [ ] Implement YAML version of `/routing` if missing features

### Feature Gap Resolution
- [ ] Add missing aliases to YAML commands
- [ ] Implement missing parameters/options in YAML
- [ ] Ensure response formatting matches PHP versions
- [ ] Add missing error handling to YAML commands
- [ ] Implement missing database interactions
- [ ] Add missing validation logic

### Integration Testing
- [ ] Test YAML commands via frontend chat interface
- [ ] Verify slash command autocomplete includes YAML commands
- [ ] Test command help text and documentation
- [ ] Validate command parameter parsing
- [ ] Test error scenarios and edge cases

## Migration Phase 🚀

### Pre-Migration Validation
- [ ] Create comprehensive test suite for all commands
- [ ] Run performance benchmarks on current PHP commands
- [ ] Document current command response times
- [ ] Create rollback plan and procedure
- [ ] Set up monitoring for command execution

### Gradual Migration
- [ ] Test YAML-only mode in development environment
- [ ] Run automated test suite against YAML commands
- [ ] Validate all command workflows still function
- [ ] Performance test YAML commands vs PHP baseline
- [ ] User acceptance testing with key workflows

### Production Cutover
- [ ] Comment out hardcoded commands in `CommandRegistry.php`
- [ ] Monitor command execution rates and errors
- [ ] Test critical user journeys still work
- [ ] Validate orchestration commands unaffected
- [ ] Address any immediate issues found

## Cleanup Phase 🧹

### Code Cleanup
- [ ] Remove commented hardcoded commands from `CommandRegistry.php`
- [ ] Delete unused PHP command classes
- [ ] Clean up command-related imports and references
- [ ] Remove legacy command configuration

### Documentation Updates
- [ ] Update command system architecture documentation  
- [ ] Document YAML DSL patterns and best practices
- [ ] Create troubleshooting guide for YAML commands
- [ ] Update development setup guides

### Final Validation
- [ ] Run complete regression test suite
- [ ] Performance validation against original baseline
- [ ] User experience spot-checking
- [ ] Code review of all changes made

## Quality Gates 🚦

### Investigation Complete
- [ ] All commands documented and analyzed
- [ ] Root cause of previous migration failure identified  
- [ ] Clear migration path established
- [ ] Risks and mitigation strategies defined

### Implementation Complete  
- [ ] All YAML commands functional and tested
- [ ] Feature parity achieved with PHP versions
- [ ] Performance within acceptable range
- [ ] Integration testing passed

### Migration Complete
- [ ] Hardcoded commands successfully disabled
- [ ] No user-facing functionality lost
- [ ] System stability maintained
- [ ] Monitoring shows healthy command execution

### Cleanup Complete
- [ ] Code is clean and maintainable
- [ ] Documentation is accurate and complete
- [ ] Team is trained on new architecture
- [ ] Migration is considered successful

## Notes
- Maintain backward compatibility throughout migration
- Coordinate with frontend team for any routing changes needed
- Test thoroughly before disabling hardcoded commands
- Document any architectural improvements discovered
- Keep orchestration commands (sprint, task, agent, backlog) unchanged