import React, { useState } from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import {
  Package,
  Loader2,
  AlertCircle,
  FileText,
  CheckCircle,
  Clock,
  TrendingUp,
  Eye,
  RefreshCw
} from 'lucide-react'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { useTypes, useTypeStats } from './hooks/useTypes'
import { TypeBadge } from './components/TypeBadge'

export function TypeSystemWidget() {
  const [showDetails, setShowDetails] = useState(false)
  const {
    data: typesData,
    isLoading: isLoadingTypes,
    error: typesError,
    refetch: refetchTypes
  } = useTypes()

  const {
    data: statsData,
    isLoading: isLoadingStats,
    error: statsError
  } = useTypeStats()

  const types = typesData?.data || []
  const stats = statsData

  const handleRefresh = () => {
    refetchTypes()
  }

  if (typesError || statsError) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Package className="w-3 h-3" />
            Type System
          </h4>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex items-center gap-2 text-red-500 text-xs py-4">
            <AlertCircle className="w-4 h-4" />
            <span>Failed to load type system data</span>
          </div>
        </CardContent>
      </Card>
    )
  }

  return (
    <Card className="border-0 shadow-none border-b border-gray-200">
      <CardHeader className="pb-1">
        <div className="flex items-center justify-between">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Package className="w-3 h-3" />
            Type System
          </h4>
          <div className="flex items-center gap-1">
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setShowDetails(!showDetails)}
              className="h-6 w-6 p-0"
            >
              <Eye className="w-3 h-3" />
            </Button>
            <Button
              variant="ghost"
              size="sm"
              onClick={handleRefresh}
              className="h-6 w-6 p-0"
              disabled={isLoadingTypes}
            >
              {isLoadingTypes ? (
                <Loader2 className="w-3 h-3 animate-spin" />
              ) : (
                <RefreshCw className="w-3 h-3" />
              )}
            </Button>
          </div>
        </div>

        {/* Summary stats */}
        {isLoadingStats ? (
          <div className="flex items-center gap-1">
            <Loader2 className="w-3 h-3 animate-spin" />
            <span className="text-xs text-muted-foreground">Loading stats...</span>
          </div>
        ) : stats ? (
          <div className="flex items-center gap-2 text-xs">
            <Badge variant="default" className="text-xs">
              <Package className="w-3 h-3 mr-1" />
              {stats.total_types} types
            </Badge>
            <Badge variant="secondary" className="text-xs">
              <FileText className="w-3 h-3 mr-1" />
              {stats.total_fragments} fragments
            </Badge>
            {stats.total_pending > 0 && (
              <Badge variant="outline" className="text-xs">
                <Clock className="w-3 h-3 mr-1" />
                {stats.total_pending} pending
              </Badge>
            )}
          </div>
        ) : null}
      </CardHeader>

      <CardContent className="pt-0">
        {isLoadingTypes ? (
          // Loading skeleton
          <div className="space-y-2">
            {[...Array(3)].map((_, i) => (
              <div key={i} className="flex items-center justify-between p-2">
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 bg-muted rounded animate-pulse"></div>
                  <div className="h-3 bg-muted rounded w-16 animate-pulse"></div>
                </div>
                <div className="h-5 bg-muted rounded w-8 animate-pulse"></div>
              </div>
            ))}
          </div>
        ) : types.length === 0 ? (
          <div className="text-center text-muted-foreground text-xs py-4">
            <Package className="w-8 h-8 mx-auto mb-2 opacity-50" />
            <p>No type packs loaded</p>
            <p className="text-xs mt-1">
              Type packs define fragment validation and behavior
            </p>
          </div>
        ) : showDetails ? (
          // Detailed view
          <ScrollArea className="h-64">
            <div className="space-y-2">
              {types.map((type) => {
                const typeStats = stats?.data?.find(s => s.slug === type.slug)
                return (
                  <TooltipProvider key={type.slug}>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <div className="flex items-center justify-between p-2 rounded hover:bg-muted/50 transition-colors">
                          <div className="flex items-center gap-2 min-w-0">
                            <TypeBadge
                              type={type}
                              size="sm"
                              showIcon={true}
                            />
                            <div className="flex flex-col min-w-0">
                              <div className="text-xs font-medium truncate">
                                {type.name}
                              </div>
                              {typeStats && (
                                <div className="text-xs text-muted-foreground">
                                  {typeStats.fragments_count} fragments
                                  {typeStats.pending_count > 0 && (
                                    <span className="text-orange-600">
                                      {' '}• {typeStats.pending_count} pending
                                    </span>
                                  )}
                                </div>
                              )}
                            </div>
                          </div>

                          <div className="flex items-center gap-1 flex-shrink-0">
                            {type.capabilities.length > 0 && (
                              <Badge variant="outline" className="text-xs">
                                {type.capabilities.length} caps
                              </Badge>
                            )}
                            <Badge variant="secondary" className="text-xs">
                              v{type.version}
                            </Badge>
                          </div>
                        </div>
                      </TooltipTrigger>
                      <TooltipContent>
                        <div className="space-y-1">
                          <div className="font-medium">{type.name}</div>
                          <div className="text-xs text-muted-foreground">
                            {type.description || 'No description available'}
                          </div>
                          {type.capabilities.length > 0 && (
                            <div className="text-xs">
                              <strong>Capabilities:</strong> {type.capabilities.join(', ')}
                            </div>
                          )}
                        </div>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                )
              })}
            </div>
          </ScrollArea>
        ) : (
          // Compact view
          <div className="space-y-2">
            <div className="grid grid-cols-2 gap-1">
              {types.slice(0, 4).map((type) => {
                const typeStats = stats?.data?.find(s => s.slug === type.slug)
                return (
                  <TooltipProvider key={type.slug}>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <div className="flex items-center justify-between p-1 rounded hover:bg-muted/50 transition-colors text-xs">
                          <TypeBadge
                            type={type}
                            size="sm"
                            showIcon={true}
                            className="max-w-[80px]"
                          />
                          {typeStats && (
                            <div className="flex items-center gap-1 text-xs text-muted-foreground">
                              <span>{typeStats.fragments_count}</span>
                              {typeStats.pending_count > 0 && (
                                <Badge variant="outline" className="text-xs h-4 px-1">
                                  {typeStats.pending_count}
                                </Badge>
                              )}
                            </div>
                          )}
                        </div>
                      </TooltipTrigger>
                      <TooltipContent>
                        <div className="space-y-1">
                          <div className="font-medium">{type.name}</div>
                          <div className="text-xs text-muted-foreground">
                            {typeStats?.fragments_count || 0} fragments
                            {typeStats?.pending_count ? ` • ${typeStats.pending_count} pending` : ''}
                          </div>
                        </div>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                )
              })}
            </div>

            {types.length > 4 && (
              <div className="text-center">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => setShowDetails(true)}
                  className="text-xs h-6"
                >
                  Show {types.length - 4} more types
                </Button>
              </div>
            )}
          </div>
        )}

        {/* Type system health indicator */}
        {stats && (
          <div className="pt-2 mt-2 border-t border-gray-100">
            <div className="flex items-center justify-between text-xs">
              <span className="text-muted-foreground">System Health</span>
              <div className="flex items-center gap-1">
                {stats.total_pending === 0 ? (
                  <div className="flex items-center gap-1 text-green-600">
                    <CheckCircle className="w-3 h-3" />
                    <span>Healthy</span>
                  </div>
                ) : (
                  <div className="flex items-center gap-1 text-orange-600">
                    <Clock className="w-3 h-3" />
                    <span>Review needed</span>
                  </div>
                )}
              </div>
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  )
}
