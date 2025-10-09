import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { ArrowLeft, HelpCircle } from 'lucide-react'
import { renderIcon } from '@/lib/icons'

interface TypeConfig {
  slug: string
  display_name: string
  icon: string | null
  color: string | null
  detail_fields: any
}

interface UnifiedDetailModalProps {
  isOpen: boolean
  onClose: () => void
  onBack: () => void
  item: any
  typeConfig: TypeConfig
}

export function UnifiedDetailModal({ isOpen, onClose, onBack, item, typeConfig }: UnifiedDetailModalProps) {
  const fields = typeConfig.detail_fields || getDefaultFields()

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-3xl max-h-[90vh]">
        <DialogHeader>
          <div className="flex items-center gap-2">
            <Button
              variant="ghost"
              size="sm"
              onClick={onBack}
              className="h-8 w-8 p-0"
            >
              <ArrowLeft className="h-4 w-4" />
            </Button>
            <div 
              className="flex-shrink-0 w-8 h-8 rounded flex items-center justify-center"
              style={{ backgroundColor: typeConfig.color || '#94a3b8' }}
            >
              {typeConfig.icon ? (
                renderIcon(typeConfig.icon, { className: 'h-4 w-4 text-white' })
              ) : (
                <HelpCircle className="h-4 w-4 text-white" />
              )}
            </div>
            <DialogTitle className="flex items-center gap-2">
              {item.title || typeConfig.display_name}
              <Badge variant="outline" className="text-xs">{typeConfig.slug}</Badge>
            </DialogTitle>
          </div>
        </DialogHeader>

        <ScrollArea className="max-h-[70vh] pr-4">
          <div className="space-y-4">
            {/* Render fields based on config */}
            {fields.map((field: any) => (
              <div key={field.key} className="space-y-2">
                <div className="text-sm font-medium text-muted-foreground">
                  {field.label}
                </div>
                <div className="text-sm">
                  {renderFieldValue(item, field)}
                </div>
              </div>
            ))}
          </div>
        </ScrollArea>

        <div className="flex justify-between gap-2 pt-4 border-t">
          <Button variant="outline" onClick={onBack}>
            Back to List
          </Button>
          <Button variant="outline" onClick={onClose}>
            Close
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  )
}

function getDefaultFields() {
  return [
    { key: 'message', label: 'Content', type: 'text' },
    { key: 'created_human', label: 'Created', type: 'text' },
    { key: 'metadata', label: 'Metadata', type: 'json' },
  ]
}

function renderFieldValue(item: any, field: any) {
  const value = item[field.key]

  if (!value) return <span className="text-muted-foreground">—</span>

  switch (field.type) {
    case 'json':
      return (
        <pre className="bg-muted p-2 rounded text-xs overflow-x-auto">
          {JSON.stringify(value, null, 2)}
        </pre>
      )
    case 'markdown':
      return <div className="prose prose-sm max-w-none">{value}</div>
    case 'badge':
      return <Badge>{value}</Badge>
    case 'text':
    default:
      return <div className="whitespace-pre-wrap">{value}</div>
  }
}
