# system-tools MCP Server

## Overview

General system management utilities

## Available Methods

### cache/clear

**Purpose**: TODO - Describe what this method does

**Parameters**:
- `param1` (required): Description
- `param2` (optional): Description

**Example**:
```bash
/system-tools cache/clear param1=value
```

**Response**:
```json
{
  "result": {
    "operation": "cacheClear",
    "data": "...",
    "timestamp": "2024-01-01T00:00:00Z"
  }
}
```

### logs/tail

**Purpose**: TODO - Describe what this method does

**Parameters**:
- `param1` (required): Description
- `param2` (optional): Description

**Example**:
```bash
/system-tools logs/tail param1=value
```

**Response**:
```json
{
  "result": {
    "operation": "logsTail",
    "data": "...",
    "timestamp": "2024-01-01T00:00:00Z"
  }
}
```

### queue/status

**Purpose**: TODO - Describe what this method does

**Parameters**:
- `param1` (required): Description
- `param2` (optional): Description

**Example**:
```bash
/system-tools queue/status param1=value
```

**Response**:
```json
{
  "result": {
    "operation": "queueStatus",
    "data": "...",
    "timestamp": "2024-01-01T00:00:00Z"
  }
}
```

### config/get

**Purpose**: TODO - Describe what this method does

**Parameters**:
- `param1` (required): Description
- `param2` (optional): Description

**Example**:
```bash
/system-tools config/get param1=value
```

**Response**:
```json
{
  "result": {
    "operation": "configGet",
    "data": "...",
    "timestamp": "2024-01-01T00:00:00Z"
  }
}
```

## Implementation Notes

TODO - Add implementation details, dependencies, and limitations

## Testing

```bash
# Test server directly
php artisan system-tools:mcp

# Test specific method
echo '{"method":"cache/clear","params":{}}' | php artisan system-tools:mcp
```
