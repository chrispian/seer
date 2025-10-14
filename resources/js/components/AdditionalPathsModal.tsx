import { useState, useEffect } from 'react'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { FolderOpen, X, Plus } from 'lucide-react'

interface AdditionalPathsModalProps {
  isOpen: boolean
  onClose: () => void
  sessionId: number | null
  initialPaths?: string[]
  onPathsChange?: (paths: string[]) => void
}

export function AdditionalPathsModal({
  isOpen,
  onClose,
  sessionId,
  initialPaths = [],
  onPathsChange,
}: AdditionalPathsModalProps) {
  const [paths, setPaths] = useState<string[]>(initialPaths)
  const [newPath, setNewPath] = useState('')
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (isOpen) {
      setPaths(initialPaths)
      setNewPath('')
      setError(null)
    }
  }, [isOpen, initialPaths])

  const handleAddPath = () => {
    const trimmed = newPath.trim()
    if (!trimmed) return

    if (paths.includes(trimmed)) {
      setError('This path is already added')
      return
    }

    setPaths([...paths, trimmed])
    setNewPath('')
    setError(null)
  }

  const handleRemovePath = (pathToRemove: string) => {
    setPaths(paths.filter(p => p !== pathToRemove))
  }

  const handleSave = async () => {
    if (!sessionId) {
      setError('No session selected')
      return
    }

    setSaving(true)
    setError(null)

    try {
      const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
      
      const response = await fetch(`/api/chat-sessions/${sessionId}/paths`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
        },
        body: JSON.stringify({ additional_paths: paths }),
      })

      if (!response.ok) {
        const errorData = await response.json()
        throw new Error(errorData.message || 'Failed to save paths')
      }

      onPathsChange?.(paths)
      onClose()
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to save paths')
    } finally {
      setSaving(false)
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-[600px]">
        <DialogHeader>
          <DialogTitle>Manage Additional File Paths</DialogTitle>
          <DialogDescription>
            Add additional directory paths to give the chat access to files from other projects or locations.
            Paths can be absolute or relative to the project root.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4 py-4">
          {/* Add new path */}
          <div className="flex gap-2">
            <div className="flex-1">
              <Label htmlFor="new-path" className="sr-only">
                New path
              </Label>
              <Input
                id="new-path"
                placeholder="/path/to/directory or ../relative/path"
                value={newPath}
                onChange={(e) => setNewPath(e.target.value)}
                onKeyDown={(e) => {
                  if (e.key === 'Enter') {
                    e.preventDefault()
                    handleAddPath()
                  }
                }}
              />
            </div>
            <Button
              type="button"
              size="icon"
              onClick={handleAddPath}
              disabled={!newPath.trim()}
            >
              <Plus className="h-4 w-4" />
            </Button>
          </div>

          {error && (
            <div className="text-sm text-destructive">{error}</div>
          )}

          {/* List of paths */}
          <div className="space-y-2">
            <Label>Additional Paths ({paths.length})</Label>
            {paths.length === 0 ? (
              <div className="text-sm text-muted-foreground py-4 text-center border border-dashed rounded-md">
                No additional paths configured.
              </div>
            ) : (
              <div className="border rounded-md divide-y max-h-[300px] overflow-y-auto">
                {paths.map((path, index) => (
                  <div
                    key={index}
                    className="flex items-center gap-2 p-2 hover:bg-accent"
                  >
                    <FolderOpen className="h-4 w-4 text-muted-foreground shrink-0" />
                    <span className="flex-1 text-sm truncate font-mono">
                      {path}
                    </span>
                    <Button
                      type="button"
                      variant="ghost"
                      size="icon"
                      className="h-6 w-6 shrink-0"
                      onClick={() => handleRemovePath(path)}
                    >
                      <X className="h-3 w-3" />
                    </Button>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>

        <DialogFooter>
          <Button
            type="button"
            variant="outline"
            onClick={onClose}
            disabled={saving}
          >
            Cancel
          </Button>
          <Button
            type="button"
            onClick={handleSave}
            disabled={saving}
          >
            {saving ? 'Saving...' : 'Save Paths'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
