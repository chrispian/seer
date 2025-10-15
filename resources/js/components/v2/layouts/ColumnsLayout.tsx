import type { ComponentConfig } from '../types'

interface ColumnsLayoutProps {
  config: ComponentConfig
  children: React.ReactNode
}

export function ColumnsLayout({ children }: ColumnsLayoutProps) {
  return <div className="flex flex-row gap-4">{children}</div>
}
