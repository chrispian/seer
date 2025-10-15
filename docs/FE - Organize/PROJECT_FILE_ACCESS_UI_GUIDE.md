# Project File Access - UI Guide

## What You Should See

### Chat Toolbar (Bottom of Chat Input)

After the build completes and you refresh your browser, you'll see:

```
┌─────────────────────────────────────────────────────────────────┐
│ Type a message...                                               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────────┐
│ [🤖 OpenAI: GPT-4o]  [📁 General (/Users/...)]  [📂]  Agent  Chat │
│                        ↑ Project Selector      ↑ Paths Button  │
└─────────────────────────────────────────────────────────────────┘
```

### UI Components

#### 1. **Project Selector Dropdown**
- **Location**: Chat toolbar, after the model selector
- **Icon**: 📁 (FolderOpen)
- **Shows**: Project name + path (truncated)
- **Click**: Opens dropdown to select different projects
- **Example**: "General (/Users/chrispian/Projects/seer)"

#### 2. **File Paths Button**
- **Location**: Chat toolbar, after project selector
- **Icon**: 📂 (FolderOpen, no text)
- **Click**: Opens "Manage Additional File Paths" modal
- **Purpose**: Add extra directories beyond project path

#### 3. **Additional Paths Modal** (When you click folder button)
```
┌─────────────────────────────────────────────┐
│  Manage Additional File Paths          ✕   │
├─────────────────────────────────────────────┤
│  Add additional directory paths to give     │
│  the chat access to files from other        │
│  projects or locations.                     │
│                                             │
│  [/path/to/directory]  [+]                 │
│                                             │
│  Additional Paths (0)                       │
│  ┌─────────────────────────────────────┐  │
│  │  No additional paths configured.    │  │
│  └─────────────────────────────────────┘  │
│                                             │
│              [Cancel]  [Save Paths]         │
└─────────────────────────────────────────────┘
```

## How to Test

### 1. Verify UI Elements Appear
1. Open your Seer app in the browser
2. Go to a chat session
3. Look at the bottom toolbar (below the message input)
4. You should see:
   - Model selector (existing)
   - **NEW**: Project selector dropdown
   - **NEW**: Folder icon button

### 2. Test Project Selector
1. Click the project dropdown
2. Should show: "General (/Users/chrispian/Projects/seer)"
3. Dropdown opens showing available projects
4. Select a different project (if you have multiple)
5. Selection persists across page reloads

### 3. Test Additional Paths Modal
1. Click the folder icon button (📂)
2. Modal opens: "Manage Additional File Paths"
3. Add a test path (e.g., `/Users/chrispian/Documents`)
4. Click "Save Paths"
5. Modal closes
6. Re-open modal - path should be saved

### 4. Test File Operations (via Chat)
Once the UI is working, test file operations:

```
User: "List the files in the app/Models directory"
```

Expected: AI uses `project_fs` tool to list files

```
User: "Read the README.md file"
```

Expected: AI reads and summarizes the file

```
User: "Create a test file in storage/app/test.txt with content 'Hello World'"
```

Expected: AI requests approval before writing

## Troubleshooting UI

### Project Selector Not Showing
**Check:**
1. Frontend build completed: `npm run build` or `npm run dev`
2. Browser cache cleared (hard refresh: Cmd+Shift+R)
3. Console for JavaScript errors: Open DevTools → Console

### Folder Button Not Showing
**Check:**
1. Same as above
2. Verify `ChatToolbar.tsx` has `FolderOpen` import and button

### Dropdown/Modal Not Opening
**Check:**
1. Browser console for errors
2. Network tab - check if `/api/chat/projects` endpoint returns data
3. Verify CSRF token is present in page

### "No projects available"
**Solution:**
```bash
php artisan tinker
$project = \App\Models\Project::first();
$project->path = '/Users/yourname/Projects/yourproject';
$project->save();
```

## Expected API Calls

When you interact with the UI, these API calls should happen:

### On Chat Page Load
```
GET /api/chat/projects
Response: { success: true, data: [{ id: 1, name: "General", path: "..." }] }
```

### When Changing Project
```
PUT /api/chat-sessions/123/project
Body: { project_id: 1 }
```

### When Saving Additional Paths
```
PUT /api/chat-sessions/123/paths
Body: { additional_paths: ["/path/one", "/path/two"] }
```

## Debugging Tips

### Check Component Rendered
Open browser DevTools → Elements, search for:
- `data-radix-popper` (dropdown menus)
- `CompactProjectPicker`
- Look for folder icon SVG

### Check State
Add to ChatIsland temporarily:
```typescript
console.log('Selected Project:', selectedProject)
console.log('Additional Paths:', additionalPaths)
```

### Check API Responses
Network tab in DevTools:
- Filter by "chat" or "projects"
- Check response bodies
- Verify 200 status codes

## What's Working Now

✅ **Backend**
- Database schema (path, additional_paths)
- API endpoints
- ProjectFileSystemTool registered
- Tool can read/write files

✅ **Frontend (Built)**
- Components compiled
- Dev server running
- Assets ready

🔄 **Next: Browser Testing**
1. Refresh your browser
2. Check toolbar for new UI elements
3. Test project selection
4. Test path management
5. Try file operations via chat

## Quick Verification Command

```bash
# Check if tool is working
php artisan tinker
$tool = app(\App\Services\Tools\ToolRegistry::class)->get('project_fs');
$session = \App\Models\ChatSession::first();
$result = $tool->call([
    'op' => 'list',
    'path' => 'app/Models'
], [
    'session_id' => $session->id
]);
print_r($result);
```

This should list files in your app/Models directory!
