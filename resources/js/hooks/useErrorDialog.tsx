import { useState } from 'react'
import { ErrorDialog } from '@/components/ui/error-dialog'

interface ErrorState {
  title?: string
  message: string
  details?: string
}

export function useErrorDialog() {
  const [error, setError] = useState<ErrorState | null>(null)

  const showError = (message: string, details?: string, title?: string) => {
    setError({ message, details, title })
  }

  const clearError = () => {
    setError(null)
  }

  const ErrorComponent = error ? (
    <ErrorDialog
      open={!!error}
      onOpenChange={(open) => !open && clearError()}
      title={error.title}
      message={error.message}
      details={error.details}
    />
  ) : null

  return {
    showError,
    clearError,
    ErrorDialog: ErrorComponent,
  }
}
