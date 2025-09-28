import React, { useState } from 'react'
import { Card, CardContent } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible'
import { ChevronDown, ChevronRight, Clock, DollarSign, Zap, Brain, Tool } from 'lucide-react'
import { ToolCallData } from '../types'

interface ToolCallCardProps {
  toolCall: ToolCallData
}

export function ToolCallCard({ toolCall }: ToolCallCardProps) {
  const [isExpanded, setIsExpanded] = useState(false)

  const formatCost = (cost: number) => {
    return cost < 0.01 ? '<$0.01' : `$${cost.toFixed(4)}`
  }

  const formatLatency = (latency: number) => {
    return latency < 1000 ? `${latency}ms` : `${(latency / 1000).toFixed(1)}s`
  }

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'tool_call':
        return <Tool className="w-3 h-3" />
      case 'cot_reasoning':
        return <Brain className="w-3 h-3" />
      default:
        return <Zap className="w-3 h-3" />
    }
  }

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'tool_call':
        return 'bg-blue-50 text-blue-700'
      case 'cot_reasoning':
        return 'bg-purple-50 text-purple-700'
      default:
        return 'bg-gray-50 text-gray-700'
    }
  }

  return (
    <Card className="border-0 shadow-none bg-muted/20 mb-2">
      <Collapsible open={isExpanded} onOpenChange={setIsExpanded}>
        <CollapsibleTrigger asChild>
          <Button
            variant="ghost"
            className="w-full p-2 h-auto justify-start hover:bg-muted/40"
          >
            <div className="flex items-center justify-between w-full">
              <div className="flex items-center gap-2 min-w-0 flex-1">
                <div className="flex-shrink-0">
                  {isExpanded ? <ChevronDown className="w-3 h-3" /> : <ChevronRight className="w-3 h-3" />}
                </div>
                <div className={`p-1 rounded ${getTypeColor(toolCall.type)}`}>
                  {getTypeIcon(toolCall.type)}
                </div>
                <div className="min-w-0 flex-1">
                  <div className="text-xs font-medium truncate">{toolCall.title}</div>
                  <div className="text-xs text-muted-foreground truncate">{toolCall.summary}</div>
                </div>
              </div>
              <div className="flex items-center gap-1 flex-shrink-0 ml-2">
                <Badge variant="outline" className="text-xs">
                  {formatLatency(toolCall.latency)}
                </Badge>
              </div>
            </div>
          </Button>
        </CollapsibleTrigger>
        
        <CollapsibleContent className="px-2 pb-2">
          <div className="space-y-2 text-xs">
            {/* Metadata Row */}
            <div className="flex items-center justify-between pt-2 border-t border-gray-100">
              <div className="flex items-center gap-3">
                <div className="flex items-center gap-1">
                  <Clock className="w-3 h-3 text-muted-foreground" />
                  <span className="text-muted-foreground">
                    {new Date(toolCall.timestamp).toLocaleTimeString()}
                  </span>
                </div>
                <div className="flex items-center gap-1">
                  <DollarSign className="w-3 h-3 text-muted-foreground" />
                  <span className="text-muted-foreground">
                    {formatCost(toolCall.cost)}
                  </span>
                </div>
              </div>
              <Badge variant="secondary" className="text-xs">
                {toolCall.model}
              </Badge>
            </div>

            {/* Token Usage */}
            <div className="flex items-center justify-between">
              <span className="text-muted-foreground">Tokens</span>
              <div className="flex gap-2">
                <Badge variant="outline" className="text-xs">
                  In: {toolCall.tokenUsage.input}
                </Badge>
                <Badge variant="outline" className="text-xs">
                  Out: {toolCall.tokenUsage.output}
                </Badge>
              </div>
            </div>

            {/* Reasoning (if available) */}
            {toolCall.metadata.reasoning && (
              <div className="space-y-1">
                <span className="text-muted-foreground">Reasoning</span>
                <div className="text-xs bg-muted p-2 rounded text-foreground leading-relaxed">
                  {toolCall.metadata.reasoning}
                </div>
              </div>
            )}

            {/* Tools Used (if available) */}
            {toolCall.metadata.tools_used && toolCall.metadata.tools_used.length > 0 && (
              <div className="space-y-1">
                <span className="text-muted-foreground">Tools Used</span>
                <div className="flex flex-wrap gap-1">
                  {toolCall.metadata.tools_used.map((tool, index) => (
                    <Badge key={index} variant="outline" className="text-xs">
                      {tool}
                    </Badge>
                  ))}
                </div>
              </div>
            )}

            {/* Confidence (if available) */}
            {toolCall.metadata.confidence && (
              <div className="flex items-center justify-between">
                <span className="text-muted-foreground">Confidence</span>
                <Badge variant="outline" className="text-xs">
                  {(toolCall.metadata.confidence * 100).toFixed(1)}%
                </Badge>
              </div>
            )}

            {/* Weights (if available) */}
            {toolCall.metadata.weights && Object.keys(toolCall.metadata.weights).length > 0 && (
              <div className="space-y-1">
                <span className="text-muted-foreground">Weights</span>
                <div className="space-y-1">
                  {Object.entries(toolCall.metadata.weights).map(([key, value]) => (
                    <div key={key} className="flex justify-between items-center">
                      <span className="text-xs">{key}</span>
                      <Badge variant="outline" className="text-xs">
                        {(value as number).toFixed(3)}
                      </Badge>
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        </CollapsibleContent>
      </Collapsible>
    </Card>
  )
}