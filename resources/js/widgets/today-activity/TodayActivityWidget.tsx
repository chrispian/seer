import React from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Activity, DollarSign, MessageSquare, Zap, TrendingUp, Loader2 } from 'lucide-react'
import { useTodayActivity } from './hooks/useTodayActivity'

export function TodayActivityWidget() {
  const { data, isLoading, error } = useTodayActivity()

  const formatCost = (cost: number) => {
    return cost < 0.01 ? '<$0.01' : `$${cost.toFixed(3)}`
  }

  const formatTokens = (tokens: number) => {
    if (tokens > 1000) {
      return `${(tokens / 1000).toFixed(1)}k`
    }
    return tokens.toString()
  }

  if (isLoading) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Activity className="w-3 h-3" />
            Recent Activity
          </h4>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex items-center justify-center py-4">
            <Loader2 className="w-4 h-4 animate-spin text-muted-foreground" />
            <span className="ml-2 text-xs text-muted-foreground">Loading...</span>
          </div>
        </CardContent>
      </Card>
    )
  }

  if (error) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Activity className="w-3 h-3" />
            Recent Activity
          </h4>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="text-xs text-red-500 py-2">
            Unable to load activity data
          </div>
        </CardContent>
      </Card>
    )
  }

  if (!data) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Activity className="w-3 h-3" />
            Recent Activity
          </h4>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="text-xs text-muted-foreground py-2">
            No activity data available
          </div>
        </CardContent>
      </Card>
    )
  }

  const tokens = data.totalTokensIn + data.totalTokensOut

  return (
    <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Activity className="w-3 h-3" />
            Recent Activity
          </h4>
        </CardHeader>
      <CardContent className="pt-0 space-y-1">
        <div className="flex justify-between items-center">
          <span className="text-xs text-muted-foreground flex items-center gap-1">
            <MessageSquare className="w-3 h-3" />
            Messages
          </span>
          <span className="text-sm font-semibold">
            {data.messages || 0}
          </span>
        </div>
        
        <div className="flex justify-between items-center">
          <span className="text-xs text-muted-foreground flex items-center gap-1">
            <Zap className="w-3 h-3" />
            Commands
          </span>
          <span className="text-sm font-semibold">
            {data.commands || 0}
          </span>
        </div>

        <div className="flex justify-between items-center">
          <span className="text-xs text-muted-foreground flex items-center gap-1">
            <TrendingUp className="w-3 h-3" />
            Tokens
          </span>
          <Badge variant="outline" className="text-xs">
            {formatTokens(tokens)}
          </Badge>
        </div>

        <div className="flex justify-between items-center">
          <span className="text-xs text-muted-foreground flex items-center gap-1">
            <DollarSign className="w-3 h-3" />
            Cost
          </span>
          <Badge variant="outline" className="text-xs">
            {formatCost(data.totalCost)}
          </Badge>
        </div>

        {((data.modelsUsed && data.modelsUsed.length > 0) || (data.providersUsed && data.providersUsed.length > 0)) && (
          <div className="pt-1 border-t border-gray-100 space-y-1">
            {data.providersUsed && data.providersUsed.length > 0 && (
              <div>
                <div className="text-xs text-muted-foreground mb-1">Providers</div>
                <div className="flex flex-wrap gap-1">
                  {data.providersUsed.map((provider, index) => (
                    <Badge key={index} variant="secondary" className="text-xs capitalize">
                      {provider}
                    </Badge>
                  ))}
                </div>
              </div>
            )}
            
            {data.modelsUsed && data.modelsUsed.length > 0 && (
              <div>
                <div className="text-xs text-muted-foreground mb-1">Models</div>
                <div className="flex flex-wrap gap-1">
                  {data.modelsUsed.map((model, index) => (
                    <Badge key={index} variant="outline" className="text-xs">
                      {model}
                    </Badge>
                  ))}
                </div>
              </div>
            )}
          </div>
        )}
      </CardContent>
    </Card>
  )
}