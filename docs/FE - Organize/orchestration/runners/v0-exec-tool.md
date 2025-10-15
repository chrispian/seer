# Exec Tool v0 - Chat-Triggered Shell Execution

## Overview

The exec tool provides chat-triggered shell command execution functionality. When a chat message begins with the `:exec-tool` prefix, the system routes the request to the ShellTool instead of the LLM, executes the command, and returns the output as a chat response.

## Configuration

Configuration is located in `config/fragments.php` under the `tools.exec_tool` section:

```php
'exec_tool' => [
    'enabled' => env('FRAGMENT_TOOLS_EXEC_ENABLED', false),
    'default_command' => env('FRAGMENT_TOOLS_EXEC_DEFAULT', 'ls -asl'),
    'workdir' => env('FRAGMENT_TOOLS_EXEC_WORKDIR'),
    'timeout_seconds' => env('FRAGMENT_TOOLS_EXEC_TIMEOUT', 20),
],
```

### Environment Variables

- `FRAGMENT_TOOLS_EXEC_ENABLED` - Enable/disable exec tool (default: `false`)
- `FRAGMENT_TOOLS_EXEC_DEFAULT` - Default command when no arguments provided (default: `ls -asl`)
- `FRAGMENT_TOOLS_EXEC_WORKDIR` - Working directory for commands (default: inherits from shell tool config)
- `FRAGMENT_TOOLS_EXEC_TIMEOUT` - Timeout in seconds (default: `20`)

### Enabling the Tool

To enable exec tool functionality:

1. Set `FRAGMENT_TOOLS_EXEC_ENABLED=true` in your `.env` file
2. Enable the shell tool: `FRAGMENT_TOOLS_SHELL_ENABLED=true`
3. Configure shell tool allowlist (required for security):
   ```
   FRAGMENT_TOOLS_SHELL_ALLOWLIST=ls,pwd,echo,cat,grep,find
   ```

## Usage

### Basic Syntax

```
:exec-tool [command]
```

### Examples

**Execute default command:**
```
:exec-tool
```
Executes `ls -asl` in the configured working directory.

**Execute specific command:**
```
:exec-tool pwd
```

```
:exec-tool ls -la
```

```
:exec-tool echo "Hello World"
```

## Security

The exec tool inherits all security features from the ShellTool:

### Command Allowlist
Only commands in the allowlist can be executed. Configure via `FRAGMENT_TOOLS_SHELL_ALLOWLIST`.

### Dangerous Character Filtering
The following characters are blocked to prevent command injection:
- `&`, `|`, `;`, `` ` ``, `$`, `(`, `)`, `<`, `>`, `"`, `'`, `\`, newlines

### Timeout Limits
- Default timeout: 20 seconds
- Maximum timeout: 300 seconds (5 minutes)
- Configurable per execution

### Output Size Limits
- stdout/stderr are each limited to 20,000 characters
- Prevents memory exhaustion from large outputs

## Implementation Details

### Flow

1. **Prefix Detection** (`ChatApiController::send`)
   - Checks if message starts with `:exec-tool`
   - Extracts command from remainder of message
   - Routes to `handleExecTool()` method

2. **Tool Execution** (`handleExecTool()`)
   - Validates exec tool is enabled
   - Creates user fragment with command metadata
   - Calls ShellTool via ToolRegistry
   - Creates assistant fragment with output
   - Updates chat session if present

3. **Response**
   - Returns JSON response (not streamed in v0)
   - Includes stdout, stderr, exit code
   - Creates fragments for chat history

### Response Format

```json
{
  "message_id": "uuid",
  "conversation_id": "uuid",
  "user_fragment_id": 123,
  "assistant_fragment_id": 124,
  "tool_output": "command output here...",
  "exit_code": 0
}
```

### Error Handling

If command execution fails:
```json
{
  "message_id": "uuid",
  "conversation_id": "uuid",
  "user_fragment_id": 123,
  "assistant_fragment_id": 124,
  "error": "Tool execution failed: error message"
}
```

## Limitations (v0)

- **No streaming**: Output is returned as complete response, not streamed token-by-token
- **No interactive commands**: Commands requiring user input will hang until timeout
- **Single command only**: No command chaining or piping (blocked by security filters)
- **Read-only recommended**: Write operations should be carefully considered and explicitly allowed

## Future Enhancements

Potential improvements for future versions:

- **SSE Streaming**: Stream command output line-by-line to chat UI
- **WebSocket Support**: Real-time bidirectional communication
- **Artifact Storage**: Save full output as `fe://` artifacts
- **Enhanced Telemetry**: Track command usage, performance, errors
- **Permission System**: User-level capability checks
- **Command Templates**: Pre-approved command patterns
- **Interactive Mode**: Handle commands requiring input

## Troubleshooting

### "Exec tool is not enabled"
Set `FRAGMENT_TOOLS_EXEC_ENABLED=true` in your environment.

### "Shell tool is disabled"
Set `FRAGMENT_TOOLS_SHELL_ENABLED=true` in your environment.

### "Command not allowed"
Add the command binary to `FRAGMENT_TOOLS_SHELL_ALLOWLIST`.

### "Command contains dangerous characters"
Remove shell control characters from your command. Only simple commands are allowed.

### Command times out
- Increase `FRAGMENT_TOOLS_EXEC_TIMEOUT` value
- Ensure command doesn't require interactive input
- Check if command is actually running (not hung)

## Related Files

- `app/Http/Controllers/ChatApiController.php` - Prefix detection and routing
- `app/Services/Tools/Providers/ShellTool.php` - Command execution
- `app/Services/Tools/ToolRegistry.php` - Tool registration
- `config/fragments.php` - Configuration

## See Also

- [Orchestration Overview](../README.md)
- [Tool System Documentation](../../tools/)
- Task specification: `delegation/backlog/T-RUNNER-V0-EXEC-TOOL.yaml`
