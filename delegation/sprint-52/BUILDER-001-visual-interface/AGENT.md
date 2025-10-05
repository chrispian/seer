# BUILDER-001: Visual Command Builder Interface

## Agent Profile
**Type**: Technical Writer (DevRel) + Senior Engineer  
**Specialization**: React/TypeScript, Drag-Drop Interfaces, Developer Experience

## Task Overview
Create a visual, drag-and-drop command builder interface that allows users to compose DSL commands through an intuitive UI with real-time validation and preview.

## Context
With deterministic DSL foundation and enhanced error handling in place, users need a visual interface to create custom commands without writing YAML manually. This democratizes command creation and improves developer productivity.

## Technical Requirements

### **Core Interface Components**

#### **1. Step Palette**
Categorized step types available for drag-and-drop:
```typescript
interface StepPalette {
  categories: {
    'Data Operations': ['model.query', 'model.create', 'model.update', 'model.delete'],
    'Text Processing': ['text.parse', 'string.format'],
    'Data Manipulation': ['context.merge', 'list.map', 'data.transform'],
    'Flow Control': ['condition', 'validate'],
    'User Interface': ['notify', 'response.panel'],
    'Utilities': ['tool.call', 'job.dispatch']
  };
  stepMetadata: Record<string, StepMetadata>;
}

interface StepMetadata {
  name: string;
  description: string;
  icon: string;
  category: string;
  configSchema: JSONSchema;
  examples: StepExample[];
  documentation: string;
}
```

#### **2. Visual Flow Canvas**
Drag-and-drop canvas for step composition:
```typescript
interface FlowCanvas {
  steps: FlowStep[];
  connections: FlowConnection[];
  layout: 'vertical' | 'horizontal' | 'auto';
  zoom: number;
  selectedStep?: string;
}

interface FlowStep {
  id: string;
  type: string;
  position: { x: number; y: number };
  config: Record<string, any>;
  errorConfig?: ErrorHandlingConfig;
  isValid: boolean;
  validationErrors: string[];
}

interface FlowConnection {
  from: string;
  to: string;
  condition?: string; // For conditional flows
}
```

#### **3. Step Configuration Panel**
Dynamic form generation for step configuration:
```typescript
interface ConfigPanel {
  stepId: string;
  stepType: string;
  schema: JSONSchema;
  currentConfig: Record<string, any>;
  validationState: ValidationState;
  templateVariables: TemplateVariable[];
}

interface TemplateVariable {
  name: string;
  type: 'string' | 'number' | 'boolean' | 'array' | 'object';
  source: 'context' | 'step_output' | 'environment';
  description: string;
  example: string;
}
```

## Implementation Architecture

### **React Component Structure**
```
CommandBuilder/
├── CommandBuilder.tsx           # Main container
├── StepPalette/
│   ├── StepPalette.tsx         # Step selection sidebar
│   ├── StepCategory.tsx        # Collapsible category
│   └── StepTile.tsx           # Draggable step tile
├── FlowCanvas/
│   ├── FlowCanvas.tsx          # Main canvas area
│   ├── FlowStep.tsx           # Individual step node
│   ├── FlowConnection.tsx     # Connection lines
│   └── CanvasControls.tsx     # Zoom, layout controls
├── ConfigPanel/
│   ├── ConfigPanel.tsx         # Configuration sidebar
│   ├── DynamicForm.tsx        # Schema-driven form
│   ├── TemplateEditor.tsx     # Template string editing
│   └── ErrorConfigPanel.tsx   # Error handling config
├── Preview/
│   ├── PreviewPanel.tsx        # Command preview/testing
│   ├── YAMLPreview.tsx        # Generated YAML display
│   └── TestRunner.tsx         # Dry run execution
└── Toolbar/
    ├── CommandToolbar.tsx      # Save, test, deploy actions
    ├── ValidationIndicator.tsx # Real-time validation status
    └── HelpPanel.tsx          # Context-sensitive help
```

### **State Management with Zustand**
```typescript
interface CommandBuilderState {
  // Command metadata
  command: {
    name: string;
    description: string;
    triggers: {
      slash: string;
    };
    requires: {
      capabilities: string[];
      secrets: string[];
    };
  };
  
  // Flow state
  steps: FlowStep[];
  connections: FlowConnection[];
  selectedStep?: string;
  
  // UI state
  activePanel: 'config' | 'preview' | 'help';
  validationErrors: ValidationError[];
  isDirty: boolean;
  
  // Actions
  addStep: (stepType: string, position: { x: number; y: number }) => void;
  updateStepConfig: (stepId: string, config: Record<string, any>) => void;
  connectSteps: (fromId: string, toId: string) => void;
  validateCommand: () => ValidationResult;
  generateYAML: () => string;
  saveCommand: () => Promise<void>;
  testCommand: (context?: Record<string, any>) => Promise<TestResult>;
}
```

### **Drag and Drop Implementation**
```typescript
// Using @dnd-kit for drag and drop
import { DndContext, DragEndEvent, DragOverlay } from '@dnd-kit/core';

function CommandBuilder() {
  const { steps, addStep, connectSteps } = useCommandBuilderStore();
  
  const handleDragEnd = (event: DragEndEvent) => {
    const { active, over } = event;
    
    if (over?.id === 'canvas') {
      // Adding new step to canvas
      const stepType = active.data.current?.stepType;
      const position = calculateDropPosition(event);
      addStep(stepType, position);
    } else if (over?.data.current?.stepId) {
      // Connecting steps
      const fromStepId = active.data.current?.stepId;
      const toStepId = over.data.current.stepId;
      connectSteps(fromStepId, toStepId);
    }
  };
  
  return (
    <DndContext onDragEnd={handleDragEnd}>
      <div className="flex h-screen">
        <StepPalette />
        <FlowCanvas steps={steps} />
        <ConfigPanel />
      </div>
    </DndContext>
  );
}
```

### **Real-time Validation**
```typescript
interface ValidationEngine {
  validateStep: (step: FlowStep) => ValidationResult;
  validateFlow: (steps: FlowStep[], connections: FlowConnection[]) => ValidationResult;
  validateTemplate: (template: string, availableVariables: string[]) => ValidationResult;
}

const useValidation = () => {
  const { steps, connections } = useCommandBuilderStore();
  
  const validationResult = useMemo(() => {
    const stepValidations = steps.map(step => validateStep(step));
    const flowValidation = validateFlow(steps, connections);
    
    return {
      isValid: stepValidations.every(v => v.isValid) && flowValidation.isValid,
      errors: [...stepValidations.flatMap(v => v.errors), ...flowValidation.errors],
    };
  }, [steps, connections]);
  
  return validationResult;
};
```

## Advanced Features

### **Template Variable Assistance**
```typescript
interface TemplateAssistance {
  availableVariables: TemplateVariable[];
  autoComplete: (partial: string) => TemplateVariable[];
  validateTemplate: (template: string) => { isValid: boolean; errors: string[] };
  previewOutput: (template: string, sampleData: Record<string, any>) => string;
}

// Template editor with autocomplete
function TemplateEditor({ value, onChange, availableVariables }: TemplateEditorProps) {
  const [suggestions, setSuggestions] = useState<TemplateVariable[]>([]);
  
  const handleInputChange = (newValue: string) => {
    onChange(newValue);
    
    // Trigger autocomplete for {{ expressions
    const cursorPosition = getCursorPosition();
    const templateMatch = newValue.slice(0, cursorPosition).match(/\{\{\s*([^}]*)$/);
    
    if (templateMatch) {
      const partial = templateMatch[1];
      setSuggestions(autoComplete(partial, availableVariables));
    } else {
      setSuggestions([]);
    }
  };
  
  return (
    <div className="relative">
      <textarea 
        value={value}
        onChange={handleInputChange}
        className="template-editor"
      />
      {suggestions.length > 0 && (
        <AutocompleteSuggestions suggestions={suggestions} />
      )}
    </div>
  );
}
```

### **Visual Flow Indicators**
```typescript
// Visual indicators for data flow
function FlowStep({ step, connections }: FlowStepProps) {
  const hasError = step.validationErrors.length > 0;
  const isConnected = connections.some(c => c.from === step.id || c.to === step.id);
  
  return (
    <div className={clsx(
      'flow-step',
      hasError && 'has-error',
      !isConnected && 'unconnected'
    )}>
      <div className="step-header">
        <StepIcon type={step.type} />
        <span className="step-name">{step.type}</span>
        {hasError && <ErrorIcon />}
      </div>
      
      <div className="step-body">
        <StepOutputs step={step} />
      </div>
      
      <ConnectionPorts stepId={step.id} />
    </div>
  );
}
```

### **Command Testing Interface**
```typescript
interface TestRunner {
  runDryRun: (command: CommandSpec, context: Record<string, any>) => Promise<TestResult>;
  runLiveTest: (command: CommandSpec, context: Record<string, any>) => Promise<TestResult>;
  generateTestCases: (command: CommandSpec) => TestCase[];
}

function PreviewPanel() {
  const { generateYAML, testCommand } = useCommandBuilderStore();
  const [testResult, setTestResult] = useState<TestResult | null>(null);
  const [testContext, setTestContext] = useState<Record<string, any>>({});
  
  const handleTest = async (mode: 'dry' | 'live') => {
    const result = await testCommand(testContext, mode === 'dry');
    setTestResult(result);
  };
  
  return (
    <div className="preview-panel">
      <Tabs>
        <TabPanel label="YAML">
          <YAMLPreview yaml={generateYAML()} />
        </TabPanel>
        
        <TabPanel label="Test">
          <TestContextEditor 
            context={testContext}
            onChange={setTestContext}
          />
          <TestControls onTest={handleTest} />
          {testResult && <TestResultDisplay result={testResult} />}
        </TabPanel>
      </Tabs>
    </div>
  );
}
```

## Success Criteria
- [ ] Drag-and-drop functionality for all step types
- [ ] Real-time validation with clear error indicators
- [ ] Template autocomplete with available variables
- [ ] YAML generation matches hand-written commands
- [ ] Dry-run testing shows step-by-step execution
- [ ] Save/load functionality for command persistence
- [ ] Responsive design for different screen sizes
- [ ] Accessibility compliance (keyboard navigation, screen readers)

## Integration Points
- **Backend**: API endpoints for command CRUD operations
- **Schema**: Real-time schema fetching from StepFactory
- **Testing**: Integration with CommandRunner for dry-run execution
- **Storage**: Database persistence for user-created commands
- **Authentication**: User session management for command ownership

## Performance Targets
- **Initial Load**: <2 seconds for builder interface
- **Drag Operations**: <16ms response time for smooth interaction
- **Validation**: <100ms for real-time validation feedback
- **YAML Generation**: <50ms for command serialization
- **Test Execution**: <500ms for dry-run testing

This visual builder transforms DSL command creation from a technical writing task to an intuitive visual composition experience, making custom commands accessible to non-technical users while maintaining the full power of the DSL framework.