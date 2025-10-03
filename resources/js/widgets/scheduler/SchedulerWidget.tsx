import React, { useState } from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { ScrollArea } from '@/components/ui/scroll-area'
import { Badge } from '@/components/ui/badge'
import { 
  Calendar, 
  Loader2, 
  AlertCircle, 
  Clock,
  Play,
  CheckCircle,
  XCircle,
  Eye,
  RefreshCw,
  AlertTriangle,
  Zap
} from 'lucide-react'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { useSchedulerStats, useSchedules, useScheduleRuns } from './hooks/useScheduler'
import { ScheduleStatusBadge, RunStatusBadge } from './components/ScheduleStatusBadge'

export function SchedulerWidget() {
  const [showDetails, setShowDetails] = useState(false)
  const [activeTab, setActiveTab] = useState<'schedules' | 'runs'>('schedules')
  
  const { 
    data: statsData, 
    isLoading: isLoadingStats, 
    error: statsError 
  } = useSchedulerStats()
  
  const { 
    data: schedulesData, 
    isLoading: isLoadingSchedules, 
    error: schedulesError,
    refetch: refetchSchedules
  } = useSchedules('active', 10)
  
  const { 
    data: runsData, 
    isLoading: isLoadingRuns, 
    error: runsError 
  } = useScheduleRuns(undefined, 10)

  const schedules = schedulesData?.data || []
  const runs = runsData?.data || []
  const stats = statsData

  const handleRefresh = () => {
    refetchSchedules()
  }

  const formatTimeAgo = (dateString: string) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60))
    
    if (diffInMinutes < 1) return 'Just now'
    if (diffInMinutes < 60) return `${diffInMinutes}m ago`
    const diffInHours = Math.floor(diffInMinutes / 60)
    if (diffInHours < 24) return `${diffInHours}h ago`
    const diffInDays = Math.floor(diffInHours / 24)
    return `${diffInDays}d ago`
  }

  const formatDuration = (durationMs?: number) => {
    if (!durationMs) return 'N/A'
    if (durationMs < 1000) return `${durationMs}ms`
    const seconds = Math.floor(durationMs / 1000)
    if (seconds < 60) return `${seconds}s`
    const minutes = Math.floor(seconds / 60)
    return `${minutes}m ${seconds % 60}s`
  }

  if (statsError || schedulesError || runsError) {
    return (
      <Card className="border-0 shadow-none border-b border-gray-200">
        <CardHeader className="pb-1">
          <h4 className="text-xs font-medium flex items-center gap-1">
            <Calendar className="w-3 h-3" />
            Scheduler
          </h4>
        </CardHeader>
        <CardContent className="pt-0">
          <div className="flex items-center gap-2 text-red-500 text-xs py-4">
            <AlertCircle className="w-4 h-4" />
            <span>Failed to load scheduler data</span>
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
            <Calendar className="w-3 h-3" />
            Scheduler
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
              disabled={isLoadingSchedules}
            >
              {isLoadingSchedules ? (
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
              <Calendar className="w-3 h-3 mr-1" />
              {stats.active} active
            </Badge>
            {stats.due > 0 && (
              <Badge variant="destructive" className="text-xs">
                <AlertTriangle className="w-3 h-3 mr-1" />
                {stats.due} due
              </Badge>
            )}
            {stats.recent_runs.running_now > 0 && (
              <Badge variant="outline" className="text-xs">
                <Play className="w-3 h-3 mr-1" />
                {stats.recent_runs.running_now} running
              </Badge>
            )}
          </div>
        ) : null}
      </CardHeader>

      <CardContent className="pt-0">
        {showDetails && (
          <div className="mb-3">
            <div className="flex items-center gap-1 mb-2">
              <Button
                variant={activeTab === 'schedules' ? 'default' : 'outline'}
                size="sm"
                onClick={() => setActiveTab('schedules')}
                className="h-6 text-xs"
              >
                Schedules
              </Button>
              <Button
                variant={activeTab === 'runs' ? 'default' : 'outline'}
                size="sm"
                onClick={() => setActiveTab('runs')}
                className="h-6 text-xs"
              >
                Recent Runs
              </Button>
            </div>
          </div>
        )}

        {isLoadingSchedules && isLoadingRuns ? (
          // Loading skeleton
          <div className="space-y-2">
            {[...Array(3)].map((_, i) => (
              <div key={i} className="flex items-center justify-between p-2">
                <div className="flex items-center gap-2">
                  <div className="w-4 h-4 bg-muted rounded animate-pulse"></div>
                  <div className="h-3 bg-muted rounded w-24 animate-pulse"></div>
                </div>
                <div className="h-5 bg-muted rounded w-16 animate-pulse"></div>
              </div>
            ))}
          </div>
        ) : showDetails ? (
          // Detailed view
          <ScrollArea className="h-64">
            {activeTab === 'schedules' ? (
              <div className="space-y-2">
                {schedules.length === 0 ? (
                  <div className="text-center text-muted-foreground text-xs py-4">
                    <Calendar className="w-8 h-8 mx-auto mb-2 opacity-50" />
                    <p>No active schedules</p>
                  </div>
                ) : (
                  schedules.map((schedule) => (
                    <TooltipProvider key={schedule.id}>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <div className="flex items-center justify-between p-2 rounded hover:bg-muted/50 transition-colors">
                            <div className="flex items-center gap-2 min-w-0">
                              <div className="flex flex-col min-w-0">
                                <div className="text-xs font-medium truncate">
                                  {schedule.name}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                  {schedule.command_slug}
                                </div>
                                {schedule.next_run_at && (
                                  <div className="text-xs text-muted-foreground">
                                    Next: {formatTimeAgo(schedule.next_run_at)}
                                  </div>
                                )}
                              </div>
                            </div>
                            
                            <div className="flex flex-col items-end gap-1 flex-shrink-0">
                              <ScheduleStatusBadge 
                                status={schedule.status}
                                isDue={schedule.is_due}
                                isLocked={schedule.is_locked}
                                size="sm"
                              />
                              <Badge variant="outline" className="text-xs">
                                {schedule.run_count} runs
                              </Badge>
                            </div>
                          </div>
                        </TooltipTrigger>
                        <TooltipContent>
                          <div className="space-y-1">
                            <div className="font-medium">{schedule.name}</div>
                            <div className="text-xs text-muted-foreground">
                              Command: {schedule.command_slug}
                            </div>
                            <div className="text-xs text-muted-foreground">
                              Recurrence: {schedule.recurrence_type} {schedule.recurrence_value}
                            </div>
                            {schedule.max_runs && (
                              <div className="text-xs text-muted-foreground">
                                Progress: {schedule.run_count}/{schedule.max_runs}
                              </div>
                            )}
                          </div>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  ))
                )}
              </div>
            ) : (
              <div className="space-y-2">
                {runs.length === 0 ? (
                  <div className="text-center text-muted-foreground text-xs py-4">
                    <Zap className="w-8 h-8 mx-auto mb-2 opacity-50" />
                    <p>No recent runs</p>
                  </div>
                ) : (
                  runs.map((run) => (
                    <TooltipProvider key={run.id}>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <div className="flex items-center justify-between p-2 rounded hover:bg-muted/50 transition-colors">
                            <div className="flex items-center gap-2 min-w-0">
                              <div className="flex flex-col min-w-0">
                                <div className="text-xs font-medium truncate">
                                  {run.schedule_name}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                  {run.command_slug}
                                </div>
                                <div className="text-xs text-muted-foreground">
                                  {formatTimeAgo(run.planned_run_at)}
                                </div>
                              </div>
                            </div>
                            
                            <div className="flex flex-col items-end gap-1 flex-shrink-0">
                              <RunStatusBadge 
                                status={run.status}
                                size="sm"
                              />
                              {run.duration_ms && (
                                <Badge variant="outline" className="text-xs">
                                  {formatDuration(run.duration_ms)}
                                </Badge>
                              )}
                            </div>
                          </div>
                        </TooltipTrigger>
                        <TooltipContent>
                          <div className="space-y-1">
                            <div className="font-medium">{run.schedule_name}</div>
                            <div className="text-xs text-muted-foreground">
                              Planned: {new Date(run.planned_run_at).toLocaleString()}
                            </div>
                            {run.started_at && (
                              <div className="text-xs text-muted-foreground">
                                Started: {new Date(run.started_at).toLocaleString()}
                              </div>
                            )}
                            {run.completed_at && (
                              <div className="text-xs text-muted-foreground">
                                Completed: {new Date(run.completed_at).toLocaleString()}
                              </div>
                            )}
                            {run.error_message && (
                              <div className="text-xs text-red-500">
                                Error: {run.error_message}
                              </div>
                            )}
                          </div>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  ))
                )}
              </div>
            )}
          </ScrollArea>
        ) : (
          // Compact view
          <div className="space-y-2">
            {/* Next scheduled runs */}
            {stats?.next_runs && stats.next_runs.length > 0 && (
              <div>
                <div className="text-xs text-muted-foreground mb-1">Upcoming</div>
                <div className="space-y-1">
                  {stats.next_runs.slice(0, 3).map((nextRun) => (
                    <div key={nextRun.id} className="flex items-center justify-between text-xs">
                      <span className="truncate">{nextRun.name}</span>
                      <Badge variant="outline" className="text-xs">
                        {nextRun.time_until}
                      </Badge>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {/* Today's run summary */}
            {stats?.recent_runs && (
              <div className="pt-2 mt-2 border-t border-gray-100">
                <div className="text-xs text-muted-foreground mb-1">Today</div>
                <div className="grid grid-cols-3 gap-1 text-xs">
                  <div className="flex flex-col items-center">
                    <Badge variant="secondary" className="text-xs mb-1">
                      {stats.recent_runs.total_today}
                    </Badge>
                    <span className="text-muted-foreground">Total</span>
                  </div>
                  <div className="flex flex-col items-center">
                    <Badge variant="outline" className="text-xs mb-1 text-green-600">
                      {stats.recent_runs.completed_today}
                    </Badge>
                    <span className="text-muted-foreground">Done</span>
                  </div>
                  <div className="flex flex-col items-center">
                    <Badge variant="outline" className="text-xs mb-1 text-red-600">
                      {stats.recent_runs.failed_today}
                    </Badge>
                    <span className="text-muted-foreground">Failed</span>
                  </div>
                </div>
              </div>
            )}
          </div>
        )}

        {/* System health indicator */}
        {stats && (
          <div className="pt-2 mt-2 border-t border-gray-100">
            <div className="flex items-center justify-between text-xs">
              <span className="text-muted-foreground">Scheduler Health</span>
              <div className="flex items-center gap-1">
                {stats.due === 0 && stats.recent_runs.failed_today === 0 ? (
                  <div className="flex items-center gap-1 text-green-600">
                    <CheckCircle className="w-3 h-3" />
                    <span>Healthy</span>
                  </div>
                ) : stats.due > 0 ? (
                  <div className="flex items-center gap-1 text-red-600">
                    <AlertTriangle className="w-3 h-3" />
                    <span>Attention needed</span>
                  </div>
                ) : (
                  <div className="flex items-center gap-1 text-orange-600">
                    <XCircle className="w-3 h-3" />
                    <span>Some failures</span>
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