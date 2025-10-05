# ENG-10-05: Tool Output Formatting

## Agent Profile
**Type**: Frontend/Full-Stack Engineer
**Expertise**: React, TypeScript, Markdown rendering, syntax highlighting, UI/UX
**Focus**: Output formatting, visual presentation, user experience

## Mission
Format tool output for optimal display in the chat interface, including code syntax highlighting, structured data presentation, and error formatting.

## Current Context
- Chat interface uses React components
- Markdown rendering already available
- Tool executor returns raw output (ENG-10-03)
- Need formatted, user-friendly display

## Skills Required
- React component development
- Markdown and code formatting
- Syntax highlighting (Prism.js/highlight.js)
- Data visualization patterns
- Responsive design

## Success Metrics
- Clean, readable tool output display
- Proper syntax highlighting for code
- Collapsible sections for large outputs
- Clear error message formatting
- Consistent visual design with chat
- Mobile-responsive formatting

## Deliverables

### 1. ToolResultFormatter Service (Backend)
```php
namespace App\Services\Tools;

class ToolResultFormatter
{
    public function formatForChat(ToolResult $result): string
    public function formatForFragment(ToolResult $result): array
    public function formatError(Exception $error): string
    public function formatProgress(array $progress): string
}
```

### 2. React Display Components
```typescript
// ToolOutput.tsx
interface ToolOutputProps {
    result: ToolResult;
    isStreaming: boolean;
}

export const ToolOutput: React.FC<ToolOutputProps>

// ToolError.tsx
export const ToolError: React.FC<{error: ToolError}>

// ToolProgress.tsx
export const ToolProgress: React.FC<{progress: number}>
```

### 3. Formatting Utilities
```typescript
// formatters.ts
export const formatToolOutput = (output: string, type: OutputType): ReactNode
export const formatCodeBlock = (code: string, language: string): ReactNode
export const formatTable = (data: any[]): ReactNode
export const formatJson = (json: object): ReactNode
```

### 4. Styling Components
```css
/* Tool output specific styles */
.tool-output-container
.tool-output-header
.tool-output-body
.tool-error-container
.tool-code-block
```

## Technical Approach

### Backend Formatting
```php
public function formatForChat(ToolResult $result): string
{
    $output = "### 🔧 Tool: {$result->tool}\n\n";
    
    // Add execution metadata
    if ($result->success) {
        $output .= "✅ **Status**: Success\n";
        $output .= "⏱️ **Time**: {$result->executionTime}ms\n\n";
    } else {
        $output .= "❌ **Status**: Failed\n";
        $output .= "**Error**: {$result->error}\n\n";
    }
    
    // Format output based on type
    $output .= $this->formatOutput($result->output);
    
    return $output;
}

private function formatOutput($output): string
{
    // Detect output type and format accordingly
    if (is_array($output)) {
        return "```json\n" . json_encode($output, JSON_PRETTY_PRINT) . "\n```";
    }
    
    if ($this->isCode($output)) {
        $lang = $this->detectLanguage($output);
        return "```{$lang}\n{$output}\n```";
    }
    
    return $output;
}
```

### React Component Implementation
```tsx
export const ToolOutput: React.FC<ToolOutputProps> = ({ result, isStreaming }) => {
    const [isExpanded, setIsExpanded] = useState(true);
    
    return (
        <div className="tool-output-container">
            <div className="tool-output-header" onClick={() => setIsExpanded(!isExpanded)}>
                <span className="tool-icon">🔧</span>
                <span className="tool-name">{result.tool}</span>
                <span className="tool-status">
                    {result.success ? '✅' : '❌'}
                </span>
                <span className="tool-time">{result.executionTime}ms</span>
            </div>
            
            {isExpanded && (
                <div className="tool-output-body">
                    {isStreaming ? (
                        <StreamingOutput content={result.output} />
                    ) : (
                        <FormattedOutput content={result.output} />
                    )}
                </div>
            )}
        </div>
    );
};
```

### Syntax Highlighting
```tsx
const FormattedOutput: React.FC<{content: string}> = ({ content }) => {
    const formatted = useMemo(() => {
        // Detect content type
        const contentType = detectContentType(content);
        
        switch(contentType) {
            case 'json':
                return <JsonViewer data={JSON.parse(content)} />;
            case 'code':
                return <CodeBlock code={content} language={detectLanguage(content)} />;
            case 'table':
                return <DataTable data={parseTableData(content)} />;
            case 'markdown':
                return <MarkdownRenderer content={content} />;
            default:
                return <pre>{content}</pre>;
        }
    }, [content]);
    
    return formatted;
};
```

### Error Formatting
```tsx
export const ToolError: React.FC<{error: ToolError}> = ({ error }) => {
    return (
        <div className="tool-error-container">
            <div className="error-header">
                <span className="error-icon">⚠️</span>
                <span className="error-title">Tool Execution Failed</span>
            </div>
            <div className="error-body">
                <div className="error-message">{error.message}</div>
                {error.details && (
                    <details className="error-details">
                        <summary>Details</summary>
                        <pre>{JSON.stringify(error.details, null, 2)}</pre>
                    </details>
                )}
                {error.suggestions && (
                    <div className="error-suggestions">
                        <h4>Suggestions:</h4>
                        <ul>
                            {error.suggestions.map(s => <li key={s}>{s}</li>)}
                        </ul>
                    </div>
                )}
            </div>
        </div>
    );
};
```

## Output Types & Formatting

### 1. Code Output
- Syntax highlighting with Prism.js
- Line numbers for long code
- Copy button
- Language detection

### 2. JSON Output
- Collapsible tree view
- Syntax highlighting
- Search functionality
- Copy formatted/raw

### 3. Table Output
- Sortable columns
- Pagination for large datasets
- Export options
- Responsive design

### 4. Text Output
- Markdown rendering
- Link detection
- Preserve formatting
- Word wrap control

### 5. Error Output
- Clear error message
- Stack trace (collapsible)
- Suggestions for resolution
- Retry button

## Styling Guidelines
```css
.tool-output-container {
    @apply border rounded-lg p-4 my-2;
    @apply bg-gray-50 dark:bg-gray-900;
}

.tool-output-header {
    @apply flex items-center justify-between;
    @apply cursor-pointer hover:bg-gray-100;
}

.tool-code-block {
    @apply bg-gray-900 text-gray-100;
    @apply p-4 rounded overflow-x-auto;
}
```

## Testing Plan
1. Test various output formats
2. Verify syntax highlighting
3. Test responsive design
4. Error display testing
5. Performance with large outputs
6. Dark mode compatibility

## Dependencies
- React components
- Prism.js or highlight.js
- Markdown renderer
- Tool executor (ENG-10-03)
- Chat integration (ENG-10-04)

## Time Estimate
2-3 hours total:
- 45 min: Backend formatter service
- 45 min: React components
- 30 min: Syntax highlighting setup
- 30 min: Error formatting
- 30 min: Styling and polish