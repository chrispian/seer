# Project File Access - Quick Start Guide

## Overview
Give your chat AI access to read and write files in your codebase and documentation. The system provides:
- **Project-based access**: Select a project with a configured path
- **Additional paths**: Add extra directories (docs, configs, other projects)
- **Approval workflow**: All write operations require your explicit approval
- **Security**: Prevents access to sensitive files (.env, keys, secrets)

## Setup (5 Minutes)

### 1. Enable the Tool
Add to your `.env`:
```bash
FRAGMENT_TOOLS_PROJECT_FS_ENABLED=true
```

### 2. Configure Project Path
Go to Projects management and add a filesystem path to your project:
```
Project: "Seer"
Path: /Users/yourname/Projects/seer
```

Or use a relative path:
```
Path: ../my-project
```

### 3. Select Project in Chat
1. Open a chat session
2. Click the **project dropdown** (next to model selector)
3. Choose your project

### 4. (Optional) Add Additional Paths
1. Click the **folder icon** button
2. Add paths to other directories:
   - `/Users/yourname/Documents/project-docs`
   - `../shared-configs`
   - `/path/to/another/project`

## Usage Examples

### Read a File
```
User: "Read the README.md file and summarize the main features"
```

The AI will:
1. Use the `project_fs` tool to read `README.md`
2. Provide a summary of the content

### List Directory Contents
```
User: "What files are in the app/Models directory?"
```

The AI will:
1. List all files and subdirectories
2. Show file names and sizes

### Create or Modify a File
```
User: "Create a migration file for adding a 'status' field to the 'tasks' table"
```

The AI will:
1. Generate the migration code
2. **Request your approval** with a preview
3. You click "Approve" or "Reject"
4. On approval, the file is written to `database/migrations/`

### Search for Files
```
User: "Find all PHP files that contain 'OrchestrationService'"
```

The AI will:
1. Search through accessible paths
2. Return matching files with paths

## Tool Operations

### Available Operations
- **read**: Read file contents
- **write**: Create or modify files (requires approval)
- **list**: List directory contents
- **exists**: Check if file/directory exists
- **search**: Find files by name pattern

### Security Features

**Automatic Blocking:**
- `.env` files
- `.key`, `.pem` files
- SSH keys
- Secrets directories

**Approval Required For:**
- All write operations
- File deletion (not yet implemented)
- Operations outside project paths

### Limits
- Max file size (read): **10 MB**
- Max file size (write): **10 MB**
- Search results: Max **100 files**

## Troubleshooting

### "No project paths configured"
**Solution**: Select a project with a configured path, or add additional paths via the folder icon button.

### "Path is not accessible"
**Solution**: The requested path is outside your project's allowed paths. Add the path via the folder icon button.

### "Access to sensitive file is blocked"
**Solution**: The file contains sensitive data (keys, secrets). This is intentional for security.

### Tool shows as disabled
**Solution**: Check that `FRAGMENT_TOOLS_PROJECT_FS_ENABLED=true` in your `.env` file.

## Examples by Use Case

### Code Review
```
User: "Review the OrchestrationBugService.php file and suggest improvements"
```

### Documentation Updates
```
User: "Update the API_REFERENCE.md file to include the new /api/chat/projects endpoint"
```
*(Requires approval)*

### Configuration Changes
```
User: "Add a new route for updating project paths to routes/api.php"
```
*(Requires approval)*

### Finding Code
```
User: "Where is the ChatSession model defined?"
```

### Creating New Files
```
User: "Create a new test file for the ProjectFileSystemTool"
```
*(Requires approval)*

## Advanced Configuration

### Custom File Size Limits
Add to `.env`:
```bash
FRAGMENT_TOOLS_PROJECT_FS_MAX_FILE_SIZE=20971520  # 20MB
FRAGMENT_TOOLS_PROJECT_FS_MAX_WRITE_SIZE=5242880  # 5MB
```

### Disable Approval for Writes (Not Recommended)
```bash
FRAGMENT_TOOLS_PROJECT_FS_REQUIRE_APPROVAL=false
```

## Best Practices

1. **Start with read-only**: Test file reading before attempting writes
2. **Use specific paths**: Narrow project paths to avoid unintended access
3. **Review approvals carefully**: Check file paths and content before approving
4. **Keep additional paths minimal**: Only add paths you actively need
5. **Use version control**: Commit often so you can revert AI changes if needed

## What's Next?

- **Tool-aware turn**: Enable for multi-step file operations
- **Batch operations**: Process multiple files in one request
- **Git integration**: Auto-commit AI changes with descriptive messages
- **Diff preview**: See changes before approving writes

## Need Help?

Check the full implementation guide: `docs/OPTION_B_PROJECT_FILE_ACCESS_IMPLEMENTATION.md`
