import { useState } from 'react'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { useToast } from '@/hooks/useToast'

interface FormField {
  name: string
  label: string
  type: 'text' | 'textarea' | 'select' | 'file'
  required?: boolean
  placeholder?: string
  options?: Array<{ value: string; label: string }>
  accept?: string
}

interface FormModalProps {
  title: string
  fields: FormField[]
  submitUrl: string
  submitMethod?: string
  submitLabel?: string
  open: boolean
  onOpenChange: (open: boolean) => void
  onSuccess?: () => void
}

export function FormModal({
  title,
  fields,
  submitUrl,
  submitMethod = 'POST',
  submitLabel = 'Submit',
  open,
  onOpenChange,
  onSuccess,
}: FormModalProps) {
  const [formData, setFormData] = useState<Record<string, any>>({})
  const [files, setFiles] = useState<Record<string, File>>({})
  const [loading, setLoading] = useState(false)
  const toast = useToast()

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    console.log('FormModal: handleSubmit called', { formData, submitUrl, submitMethod })
    setLoading(true)

    try {
      const hasFiles = Object.keys(files).length > 0
      let body: FormData | string
      let headers: Record<string, string> = {
        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '',
      }

      if (hasFiles) {
        const formDataObj = new FormData()
        Object.entries(formData).forEach(([key, value]) => {
          formDataObj.append(key, value)
        })
        Object.entries(files).forEach(([key, file]) => {
          formDataObj.append(key, file)
        })
        body = formDataObj
      } else {
        headers['Content-Type'] = 'application/json'
        body = JSON.stringify(formData)
      }

      console.log('FormModal: About to fetch', { submitUrl, method: submitMethod, body })
      const response = await window.fetch(submitUrl, {
        method: submitMethod,
        headers,
        body,
      })

      console.log('FormModal: Response received', { status: response.status })
      const result = await response.json()

      if (!response.ok) {
        throw new Error(result.message || 'Submission failed')
      }

      toast.success('Success', result.message || 'Submitted successfully')
      setFormData({})
      setFiles({})
      onOpenChange(false)
      
      if (onSuccess) {
        onSuccess()
      }
    } catch (error) {
      console.error('FormModal: Error during submission', error)
      toast.error('Error', error instanceof Error ? error.message : 'Submission failed')
    } finally {
      setLoading(false)
    }
  }

  const renderField = (field: FormField) => {
    switch (field.type) {
      case 'textarea':
        return (
          <Textarea
            id={field.name}
            placeholder={field.placeholder}
            required={field.required}
            value={formData[field.name] || ''}
            onChange={(e) => setFormData({ ...formData, [field.name]: e.target.value })}
            rows={4}
          />
        )
      
      case 'select':
        return (
          <Select
            value={formData[field.name] || ''}
            onValueChange={(value) => setFormData({ ...formData, [field.name]: value })}
            required={field.required}
          >
            <SelectTrigger>
              <SelectValue placeholder={field.placeholder || 'Select...'} />
            </SelectTrigger>
            <SelectContent>
              {field.options?.map((option) => (
                <SelectItem key={option.value} value={option.value}>
                  {option.label}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        )
      
      case 'file':
        return (
          <Input
            id={field.name}
            type="file"
            accept={field.accept}
            required={field.required}
            onChange={(e) => {
              const file = e.target.files?.[0]
              if (file) {
                setFiles({ ...files, [field.name]: file })
              }
            }}
          />
        )
      
      default:
        return (
          <Input
            id={field.name}
            type="text"
            placeholder={field.placeholder}
            required={field.required}
            value={formData[field.name] || ''}
            onChange={(e) => setFormData({ ...formData, [field.name]: e.target.value })}
          />
        )
    }
  }

  return (
    <Dialog open={open} onOpenChange={(newOpen) => {
      if (!loading) {
        onOpenChange(newOpen)
      }
    }}>
      <DialogContent className="sm:max-w-2xl max-h-[80vh] overflow-y-auto" onPointerDownOutside={(e) => {
        if (loading) {
          e.preventDefault()
        }
      }} onEscapeKeyDown={(e) => {
        if (loading) {
          e.preventDefault()
        }
      }}>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
        </DialogHeader>
        <form onSubmit={handleSubmit}>
          <div className="space-y-4 py-4">
            {fields.map((field) => (
              <div key={field.name} className="space-y-2">
                <Label htmlFor={field.name}>
                  {field.label}
                  {field.required && <span className="text-destructive ml-1">*</span>}
                </Label>
                {renderField(field)}
              </div>
            ))}
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={() => onOpenChange(false)} disabled={loading}>
              Cancel
            </Button>
            <Button type="submit" disabled={loading}>
              {loading ? 'Submitting...' : submitLabel}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
