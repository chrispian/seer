import React from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Activity, DollarSign, MessageSquare, Zap, TrendingUp } from 'lucide-react'
import { useTodayActivity } from './hooks/useTodayActivity'

export function TodayActivityWidget() {
  const { data, isLoading, error } = useTodayActivity()

  if (error) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Activity className="w-3 h-3" />
            Today's Activity
          </h4>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="text-center text-muted-foreground text-xs py-2">
            Failed to load activity data
          </div>
        </CardContent>
      </Card>
    )
  }

  if (isLoading) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Activity className="w-3 h-3" />
            Today's Activity
          </h4>
        </CardHeader>
        <CardContent className="pt-0 space-y-1">
          {[...Array(4)].map((_, i) => (
            <div key={i} className="flex justify-between items-center">
              <div className="h-3 bg-muted rounded w-16 animate-pulse"></div>
              <div className="h-4 bg-muted rounded w-8 animate-pulse"></div>
            </div>
          ))}
        </CardContent>
      </Card>
    )
  }

  const formatCost = (cost: number) => {
    return cost < 0.01 ? '<$0.01' : `$${cost.toFixed(3)}`
  }

  const formatTokens = (tokens: number) => {
    if (tokens > 1000) {
      return `${(tokens / 1000).toFixed(1)}k`
    }
    return tokens.toString()
  }

  return (
    <Card className="border-0 shadow-none border-b border-gray-200">
      <CardHeader className="pb-1">
        <h4 className="text-xs font-medium flex items-center gap-1">
          <Activity className="w-3 h-3" />
          Today's Activity
        </h4>
      </CardHeader>
      <CardContent className="pt-0 space-y-1">
        <div className="flex justify-between items-center">
          <span className="text-xs text-muted-foreground flex items-center gap-1">
            <MessageSquare className="w-3 h-3" />
            Messages
          </span>
          <Badge variant="secondary">
            {data?.messages || 0}
          </Badge>
        </div>
        
        <div className="flex justify-between items-center">
          <span className="text-xs text-muted-foreground flex items-center gap-1">
            <Zap className="w-3 h-3" />
            Commands
          </span>
          <Badge variant="secondary">
            {data?.commands || 0}
          </Badge>
        </div>

        <div className="flex justify-between items-center">
          <span className="text-xs text-muted-foreground flex items-center gap-1">
            <TrendingUp className="w-3 h-3" />
            Tokens
          </span>
          <Badge variant="outline" className="text-xs">
            {formatTokens((data?.totalTokensIn || 0) + (data?.totalTokensOut || 0))}
          </Badge>
        </div>

        <div className="flex justify-between items-center">
          <span className="text-xs text-muted-foreground flex items-center gap-1">
            <DollarSign className="w-3 h-3" />
            Cost
          </span>
          <Badge variant="outline" className="text-xs">
            {formatCost(data?.totalCost || 0)}
          </Badge>
        </div>

        {data?.modelsUsed && data.modelsUsed.length > 0 && (
          <div className="pt-1 border-t border-gray-100">
            <div className="text-xs text-muted-foreground mb-1">Models Used</div>
            <div className="flex flex-wrap gap-1">
              {data.modelsUsed.map((model, index) => (
                <Badge key={index} variant="outline" className="text-xs">
                  {model}
                </Badge>
              ))}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  )
}