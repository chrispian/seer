import type { ComponentConfig } from '../types'

interface RowsLayoutProps {
  config: ComponentConfig
  children: React.ReactNode
}

export function RowsLayout({ children }: RowsLayoutProps) {
  return <div className="flex flex-col gap-4">{children}</div>
}
