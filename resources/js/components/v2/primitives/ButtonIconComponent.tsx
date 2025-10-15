import { useState } from 'react'
import { Button } from '@/components/ui/button'
import { Plus, Edit, Trash, Eye, Settings, Search, X } from 'lucide-react'
import type { ComponentConfig } from '../types'
import { useAction } from '../hooks/useAction'
import { FormModal } from '../modals/FormModal'
import { slotBinder } from '../SlotBinder'

interface ButtonIconComponentProps {
  config: ComponentConfig
}

const iconMap = {
  plus: Plus,
  edit: Edit,
  trash: Trash,
  eye: Eye,
  settings: Settings,
  search: Search,
  x: X,
}

export function ButtonIconComponent({ config }: ButtonIconComponentProps) {
  const { execute, loading } = useAction()
  const [modalOpen, setModalOpen] = useState(false)

  const handleClick = async () => {
    if (config.actions?.click) {
      if (config.actions.click.type === 'modal') {
        setModalOpen(true)
      } else {
        await execute(config.actions.click)
      }
    }
  }

  const Icon = config.props?.icon ? iconMap[config.props.icon as keyof typeof iconMap] : null
  const clickAction = config.actions?.click

  return (
    <>
      <Button onClick={handleClick} disabled={loading} size="sm" className="gap-2">
        {Icon && <Icon className="h-4 w-4" />}
        {config.props?.label}
      </Button>

      {clickAction?.type === 'modal' && clickAction.modal === 'form' && (
        <FormModal
          title={clickAction.title || 'Form'}
          fields={clickAction.fields || []}
          submitUrl={clickAction.submitUrl || ''}
          submitMethod={clickAction.submitMethod}
          submitLabel={clickAction.submitLabel}
          open={modalOpen}
          onOpenChange={setModalOpen}
          onSuccess={() => {
            if (clickAction.refreshTarget) {
              slotBinder.update(clickAction.refreshTarget, { refresh: true })
            }
          }}
        />
      )}
    </>
  )
}
