import { Button } from '@/components/ui/button'
import { Plus, Edit, Trash, Eye, Settings, Search, X } from 'lucide-react'
import type { ComponentConfig } from '../types'
import { useAction } from '../hooks/useAction'

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

  const handleClick = async () => {
    if (config.actions?.click) {
      await execute(config.actions.click)
    }
  }

  const Icon = config.props?.icon ? iconMap[config.props.icon as keyof typeof iconMap] : null

  return (
    <Button onClick={handleClick} disabled={loading} size="sm" className="gap-2">
      {Icon && <Icon className="h-4 w-4" />}
      {config.props?.label}
    </Button>
  )
}
