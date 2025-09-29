import React from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Wrench, AlertCircle } from 'lucide-react'
import { useToolCalls } from './hooks/useToolCalls'
import { ToolCallCard } from './components/ToolCallCard'

export function ToolCallsWidget() {
  const { data: toolCalls, isLoading, error } = useToolCalls()

  if (error) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Wrench className="w-3 h-3" />
            Tool Calls & Reasoning
          </h4>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex items-center justify-center text-center text-muted-foreground text-xs py-4">
            <div className="space-y-2">
              <AlertCircle className="w-4 h-4 mx-auto" />
              <div>Failed to load tool calls</div>
            </div>
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card className="border-0 shadow-none border-b border-gray-200">
      <CardHeader className="pb-1">
        <h4 className="text-xs font-medium flex items-center gap-1">
          <Wrench className="w-3 h-3" />
          Tool Calls & Reasoning
        </h4>
      </CardHeader>
      <CardContent className="pt-0">
        <ScrollArea className="h-64">
          {isLoading ? (
            // Loading skeleton
            <div className="space-y-2">
              {[...Array(3)].map((_, i) => (
                <div key={i} className="bg-muted/20 rounded p-2">
                  <div className="flex items-center gap-2">
                    <div className="w-3 h-3 bg-muted rounded animate-pulse"></div>
                    <div className="w-4 h-4 bg-muted rounded animate-pulse"></div>
                    <div className="flex-1">
                      <div className="h-3 bg-muted rounded w-full mb-1 animate-pulse"></div>
                      <div className="h-2 bg-muted rounded w-3/4 animate-pulse"></div>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          ) : !toolCalls || toolCalls.length === 0 ? (
            <div className="text-center text-muted-foreground text-xs py-8">
              <div className="space-y-2">
                <Wrench className="w-6 h-6 mx-auto opacity-50" />
                <div>No tool calls or reasoning data yet</div>
                <div className="text-xs text-muted-foreground/70">
                  Tool calls and reasoning will appear here as you interact with the AI
                </div>
              </div>
            </div>
          ) : (
            <div className="space-y-1">
              {toolCalls.map((toolCall) => (
                <ToolCallCard key={toolCall.id} toolCall={toolCall} />
              ))}
            </div>
          )}
        </ScrollArea>
      </CardContent>
    </Card>
  )
}

// TODO: This widget needs real data from the telemetry system
// For now it will show placeholder data until the backend API is implemented