# ai-providers MCP Server

## Overview

AI provider testing and management

## Available Methods

### provider/test

**Purpose**: TODO - Describe what this method does

**Parameters**:
- `param1` (required): Description
- `param2` (optional): Description

**Example**:
```bash
/ai-providers provider/test param1=value
```

**Response**:
```json
{
  "result": {
    "operation": "providerTest",
    "data": "...",
    "timestamp": "2024-01-01T00:00:00Z"
  }
}
```

### provider/usage

**Purpose**: TODO - Describe what this method does

**Parameters**:
- `param1` (required): Description
- `param2` (optional): Description

**Example**:
```bash
/ai-providers provider/usage param1=value
```

**Response**:
```json
{
  "result": {
    "operation": "providerUsage",
    "data": "...",
    "timestamp": "2024-01-01T00:00:00Z"
  }
}
```

### provider/models

**Purpose**: TODO - Describe what this method does

**Parameters**:
- `param1` (required): Description
- `param2` (optional): Description

**Example**:
```bash
/ai-providers provider/models param1=value
```

**Response**:
```json
{
  "result": {
    "operation": "providerModels",
    "data": "...",
    "timestamp": "2024-01-01T00:00:00Z"
  }
}
```

### provider/switch

**Purpose**: TODO - Describe what this method does

**Parameters**:
- `param1` (required): Description
- `param2` (optional): Description

**Example**:
```bash
/ai-providers provider/switch param1=value
```

**Response**:
```json
{
  "result": {
    "operation": "providerSwitch",
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
php artisan ai-providers:mcp

# Test specific method
echo '{"method":"provider/test","params":{}}' | php artisan ai-providers:mcp
```
