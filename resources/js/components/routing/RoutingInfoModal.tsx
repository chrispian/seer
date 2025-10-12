import React from 'react'
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from '@/components/ui/dialog'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { CheckCircle, XCircle } from 'lucide-react'
import { formatDistanceToNow } from 'date-fns'

interface RoutingData {
  current_context: {
    vault: string
    project: string
    session: string
  }
  available_routes: Record<string, string>
  routing_status: string
  timestamp: string
}

interface RoutingInfoModalProps {
  isOpen: boolean
  onClose: () => void
  routingData: RoutingData
}

export function RoutingInfoModal({
  isOpen,
  onClose,
  routingData
}: RoutingInfoModalProps) {
  const isActive = routingData.routing_status?.toLowerCase() === 'active'

  const formatTimestamp = (timestamp: string) => {
    try {
      return formatDistanceToNow(new Date(timestamp), { addSuffix: true })
    } catch {
      return timestamp
    }
  }

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Routing Information</DialogTitle>
          <DialogDescription>
            Current navigation context and available routes
          </DialogDescription>
        </DialogHeader>

        <ScrollArea className="max-h-[500px]">
          <div className="space-y-6">
            <div className="space-y-3">
              <h3 className="text-sm font-semibold">Current Context</h3>
              <div className="space-y-2 rounded-md bg-muted/50 p-4">
                <div className="flex items-start gap-2">
                  <span className="text-xs font-medium text-muted-foreground min-w-[70px]">
                    Vault:
                  </span>
                  <span className="text-xs">
                    {routingData.current_context.vault || 'None'}
                  </span>
                </div>
                <div className="flex items-start gap-2">
                  <span className="text-xs font-medium text-muted-foreground min-w-[70px]">
                    Project:
                  </span>
                  <span className="text-xs">
                    {routingData.current_context.project || 'None'}
                  </span>
                </div>
                <div className="flex items-start gap-2">
                  <span className="text-xs font-medium text-muted-foreground min-w-[70px]">
                    Session:
                  </span>
                  <span className="text-xs">
                    {routingData.current_context.session || 'None'}
                  </span>
                </div>
              </div>
            </div>

            <div className="space-y-3">
              <h3 className="text-sm font-semibold">Available Routes</h3>
              <div className="space-y-1.5">
                {Object.entries(routingData.available_routes).map(([command, description]) => (
                  <div
                    key={command}
                    className="flex items-start gap-3 rounded-md bg-muted/30 px-3 py-2"
                  >
                    <code className="text-xs font-mono text-primary min-w-[80px]">
                      {command}
                    </code>
                    <span className="text-xs text-muted-foreground flex-1">
                      {description}
                    </span>
                  </div>
                ))}
              </div>
            </div>

            <div className="flex items-center justify-between rounded-md border bg-muted/20 p-4">
              <div className="flex items-center gap-2">
                <span className="text-xs font-medium">Status:</span>
                <Badge
                  variant="outline"
                  className={`flex items-center gap-1.5 ${
                    isActive
                      ? 'bg-green-100 text-green-800 border-green-300'
                      : 'bg-gray-100 text-gray-800 border-gray-300'
                  }`}
                >
                  {isActive ? (
                    <CheckCircle className="h-3 w-3" />
                  ) : (
                    <XCircle className="h-3 w-3" />
                  )}
                  {routingData.routing_status}
                </Badge>
              </div>
              <div className="text-xs text-muted-foreground">
                Updated {formatTimestamp(routingData.timestamp)}
              </div>
            </div>
          </div>
        </ScrollArea>
      </DialogContent>
    </Dialog>
  )
}
