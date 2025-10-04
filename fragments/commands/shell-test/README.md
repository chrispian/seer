# Shell Test Command Pack

A command pack for testing the shell tool integration.

## Usage

This command demonstrates tool.call step type usage with the shell tool.

### Example Usage

```bash
/shell-test
```

## Features

- Tests shell tool integration
- Creates fragment with command output
- Shows tool invocation logging
- Demonstrates capability-based security

## Requirements

- `shell` tool must be enabled and allowed
- `echo` command must be in shell allowlist
- User must have `tool.call` capability

## Configuration

To enable this command, add to your `.env`:

```env
FRAGMENT_TOOLS_ALLOWED=shell
FRAGMENT_TOOLS_SHELL_ENABLED=true
FRAGMENT_TOOLS_SHELL_ALLOWLIST=echo,ls,pwd
```

## Output

- Creates a log fragment with shell command output
- Shows success/failure notification
- Logs tool invocation for audit trail