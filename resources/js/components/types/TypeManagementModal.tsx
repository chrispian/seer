import { TypePackManagement } from '@/components/type-system'

interface TypeManagementModalProps {
  isOpen: boolean
  onClose: () => void
}

export function TypeManagementModal({ isOpen, onClose }: TypeManagementModalProps) {
  return <TypePackManagement isOpen={isOpen} onClose={onClose} />
}
