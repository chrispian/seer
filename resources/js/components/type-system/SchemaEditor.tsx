import React from 'react'
import { useState, useEffect } from 'react'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { FileCode, CheckCircle, AlertCircle, Eye, Code } from 'lucide-react'
import { toast } from 'sonner'

interface SchemaEditorProps {
  schema: Record<string, any> | null
  onChange: (schema: Record<string, any>) => void
  onValidate?: (schema: Record<string, any>) => Promise<{ valid: boolean; errors?: string[] }>
}

export function SchemaEditor({ schema, onChange, onValidate }: SchemaEditorProps) {
  const [schemaText, setSchemaText] = useState('')
  const [validationState, setValidationState] = useState<{
    valid: boolean
    errors?: string[]
    checked: boolean
  }>({ valid: true, checked: false })
  const [showPreview, setShowPreview] = useState(false)

  useEffect(() => {
    if (schema) {
      setSchemaText(JSON.stringify(schema, null, 2))
    } else {
      setSchemaText(JSON.stringify({
        type: 'object',
        properties: {
          title: {
            type: 'string',
            description: 'The title of the item'
          }
        },
        required: ['title']
      }, null, 2))
    }
  }, [schema])

  const handleSchemaChange = (value: string) => {
    setSchemaText(value)
    setValidationState({ valid: true, checked: false })
    
    try {
      const parsed = JSON.parse(value)
      onChange(parsed)
    } catch (err) {
    }
  }

  const handleValidate = async () => {
    try {
      const parsed = JSON.parse(schemaText)
      
      if (onValidate) {
        const result = await onValidate(parsed)
        setValidationState({ ...result, checked: true })
        
        if (result.valid) {
          toast.success('Schema is valid')
        } else {
          toast.error('Schema validation failed')
        }
      } else {
        setValidationState({ valid: true, checked: true })
        toast.success('Schema is valid JSON')
      }
    } catch (err) {
      setValidationState({ 
        valid: false, 
        errors: ['Invalid JSON: ' + (err instanceof Error ? err.message : 'Unknown error')], 
        checked: true 
      })
      toast.error('Invalid JSON')
    }
  }

  const handleFormat = () => {
    try {
      const parsed = JSON.parse(schemaText)
      setSchemaText(JSON.stringify(parsed, null, 2))
      toast.success('Schema formatted')
    } catch (err) {
      toast.error('Cannot format invalid JSON')
    }
  }

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <Label className="flex items-center gap-2">
          <FileCode className="h-4 w-4" />
          JSON Schema
        </Label>
        <div className="flex items-center gap-2">
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={() => setShowPreview(!showPreview)}
          >
            {showPreview ? <Code className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            {showPreview ? 'Edit' : 'Preview'}
          </Button>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={handleFormat}
          >
            Format JSON
          </Button>
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={handleValidate}
          >
            <CheckCircle className="h-4 w-4 mr-2" />
            Validate
          </Button>
        </div>
      </div>

      {validationState.checked && (
        <Alert variant={validationState.valid ? 'default' : 'destructive'}>
          <div className="flex items-start gap-2">
            {validationState.valid ? (
              <CheckCircle className="h-4 w-4 text-green-600 mt-0.5" />
            ) : (
              <AlertCircle className="h-4 w-4 text-red-600 mt-0.5" />
            )}
            <div className="flex-1">
              <AlertDescription>
                {validationState.valid ? (
                  'Schema is valid'
                ) : (
                  <div>
                    <div className="font-medium mb-1">Validation errors:</div>
                    <ul className="list-disc list-inside space-y-1">
                      {validationState.errors?.map((error, i) => (
                        <li key={i} className="text-sm">{error}</li>
                      ))}
                    </ul>
                  </div>
                )}
              </AlertDescription>
            </div>
          </div>
        </Alert>
      )}

      {showPreview ? (
        <div className="border rounded-md p-4 bg-muted/30">
          <div className="text-xs font-mono whitespace-pre-wrap break-all">
            {schemaText}
          </div>
        </div>
      ) : (
        <textarea
          value={schemaText}
          onChange={(e) => handleSchemaChange(e.target.value)}
          className="w-full h-[400px] font-mono text-sm p-3 border rounded-md resize-none focus:outline-none focus:ring-2 focus:ring-primary"
          placeholder="Enter JSON Schema..."
          spellCheck={false}
        />
      )}

      <div className="flex items-start gap-2 text-xs text-muted-foreground">
        <Badge variant="outline" className="text-xs">Info</Badge>
        <p>
          Define the structure of your fragment type using JSON Schema. 
          This schema will validate all fragments of this type. 
          <a 
            href="https://json-schema.org/learn/getting-started-step-by-step" 
            target="_blank" 
            rel="noopener noreferrer"
            className="underline ml-1"
          >
            Learn more about JSON Schema
          </a>
        </p>
      </div>
    </div>
  )
}
