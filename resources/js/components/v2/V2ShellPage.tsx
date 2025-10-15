import { useEffect, useState } from 'react'
import { PageRenderer } from './PageRenderer'
import { registerComponents } from './registerComponents'

interface V2ShellPageProps {
  pageKey: string
}

export function V2ShellPage({ pageKey }: V2ShellPageProps) {
  const [open, setOpen] = useState(true)

  useEffect(() => {
    registerComponents()
  }, [])

  return <PageRenderer pageKey={pageKey} open={open} onOpenChange={setOpen} />
}
