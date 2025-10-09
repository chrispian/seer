import React from 'react'
import { createRoot } from 'react-dom/client'
import { bootIslands } from './boot'
import { Toaster } from 'sonner'

export function mount(id: string, node: React.ReactNode) {
  const el = document.getElementById(id)
  if (el) createRoot(el).render(<>{node}<Toaster /></>)
}

declare global { interface Window { __APP_BOOT__?: any } }

document.addEventListener('DOMContentLoaded', () => {
  const boot = window.__APP_BOOT__
  if (!boot) return // not on our shell
  bootIslands(mount, boot)
})

