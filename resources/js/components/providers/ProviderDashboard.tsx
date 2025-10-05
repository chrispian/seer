import React, { useState, useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Progress } from '@/components/ui/progress'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { 
  Activity, 
  BarChart3, 
  Clock, 
  TrendingUp, 
  TrendingDown,
  Zap,
  AlertTriangle,
  CheckCircle,
  RefreshCw,
  DollarSign,
  Users,
  Database,
  Network
} from 'lucide-react'
import { providersApi } from '@/lib/api/providers'
import type { Provider, ProviderStatistics } from '@/types/provider'

interface DashboardMetrics {
  total_requests: number
  successful_requests: number
  failed_requests: number
  average_response_time: number
  total_tokens_used: number
  estimated_cost: number
  top_models: Array<{
    model_id: string
    model_name: string
    usage_count: number
    success_rate: number
  }>
  hourly_usage: Array<{
    hour: string
    requests: number
    tokens: number
  }>
  provider_performance: Array<{
    provider_id: string
    provider_name: string
    status: string
    requests: number
    success_rate: number
    avg_response_time: number
  }>
}

interface ProviderDashboardProps {
  timeRange?: '1h' | '24h' | '7d' | '30d'
  autoRefresh?: boolean
  refreshInterval?: number
}

export function ProviderDashboard({ 
  timeRange = '24h',
  autoRefresh = true,
  refreshInterval = 30000 
}: ProviderDashboardProps) {
  const [providers, setProviders] = useState<Provider[]>([])
  const [statistics, setStatistics] = useState<ProviderStatistics | null>(null)
  const [metrics, setMetrics] = useState<DashboardMetrics | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [lastUpdated, setLastUpdated] = useState<Date>(new Date())

  // Fetch dashboard data
  const fetchDashboardData = async () => {
    try {
      setError(null)
      
      // Fetch in parallel for better performance
      const [providersData, statisticsData] = await Promise.all([
        providersApi.getProviders(),
        providersApi.getStatistics()
      ])
      
      setProviders(providersData)
      setStatistics(statisticsData)
      
      // Mock metrics data - in real implementation, this would come from an API
      const mockMetrics: DashboardMetrics = {
        total_requests: 12547,
        successful_requests: 12123,
        failed_requests: 424,
        average_response_time: 1250,
        total_tokens_used: 8945632,
        estimated_cost: 127.43,
        top_models: [
          { model_id: 'gpt-4', model_name: 'GPT-4', usage_count: 5234, success_rate: 98.2 },
          { model_id: 'claude-3', model_name: 'Claude 3', usage_count: 3421, success_rate: 99.1 },
          { model_id: 'gpt-3.5', model_name: 'GPT-3.5 Turbo', usage_count: 2156, success_rate: 97.8 }
        ],
        hourly_usage: Array.from({ length: 24 }, (_, i) => ({
          hour: `${23 - i}:00`,
          requests: Math.floor(Math.random() * 500) + 100,
          tokens: Math.floor(Math.random() * 50000) + 10000
        })).reverse(),
        provider_performance: providersData.map(provider => ({
          provider_id: provider.id,
          provider_name: provider.name,
          status: provider.status,
          requests: Math.floor(Math.random() * 1000) + 100,
          success_rate: 95 + Math.random() * 5,
          avg_response_time: 800 + Math.random() * 1000
        }))
      }
      
      setMetrics(mockMetrics)
      setLastUpdated(new Date())
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to load dashboard data')
    } finally {
      setLoading(false)
    }
  }

  // Initial load
  useEffect(() => {
    fetchDashboardData()
  }, [timeRange])

  // Auto-refresh
  useEffect(() => {
    if (!autoRefresh) return
    
    const interval = setInterval(fetchDashboardData, refreshInterval)
    return () => clearInterval(interval)
  }, [autoRefresh, refreshInterval])

  // Calculate success rate
  const successRate = metrics 
    ? ((metrics.successful_requests / metrics.total_requests) * 100).toFixed(1)
    : '0'

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner className="h-8 w-8" />
      </div>
    )
  }

  if (error) {
    return (
      <Alert variant="destructive">
        <AlertTriangle className="h-4 w-4" />
        <AlertDescription>{error}</AlertDescription>
      </Alert>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Provider Dashboard</h1>
          <p className="text-muted-foreground">
            Monitor AI provider performance and usage analytics
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Badge variant="outline">
            Last updated: {lastUpdated.toLocaleTimeString()}
          </Badge>
          <Button 
            onClick={fetchDashboardData} 
            variant="outline" 
            size="sm"
            className="flex items-center gap-2"
          >
            <RefreshCw className="h-4 w-4" />
            Refresh
          </Button>
        </div>
      </div>

      {/* Key Metrics */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Requests</CardTitle>
            <Activity className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {metrics?.total_requests.toLocaleString()}
            </div>
            <div className="flex items-center gap-1 text-xs text-green-600">
              <TrendingUp className="h-3 w-3" />
              +12.3% from last period
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Success Rate</CardTitle>
            <CheckCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{successRate}%</div>
            <div className="flex items-center gap-1 text-xs text-green-600">
              <TrendingUp className="h-3 w-3" />
              +0.8% from last period
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Avg Response Time</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{metrics?.average_response_time}ms</div>
            <div className="flex items-center gap-1 text-xs text-red-600">
              <TrendingDown className="h-3 w-3" />
              +2.1% from last period
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Estimated Cost</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              ${metrics?.estimated_cost.toFixed(2)}
            </div>
            <div className="flex items-center gap-1 text-xs text-red-600">
              <TrendingUp className="h-3 w-3" />
              +8.4% from last period
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Detailed Analytics */}
      <Tabs defaultValue="overview" className="space-y-4">
        <TabsList>
          <TabsTrigger value="overview">Overview</TabsTrigger>
          <TabsTrigger value="providers">Providers</TabsTrigger>
          <TabsTrigger value="models">Models</TabsTrigger>
          <TabsTrigger value="usage">Usage</TabsTrigger>
        </TabsList>

        <TabsContent value="overview" className="space-y-4">
          <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
            {/* Provider Status Overview */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Network className="h-5 w-5" />
                  Provider Status
                </CardTitle>
                <CardDescription>Current health status of all providers</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  {providers.map(provider => (
                    <div key={provider.id} className="flex items-center justify-between">
                      <div className="flex items-center gap-3">
                        <div className={`w-2 h-2 rounded-full ${
                          provider.status === 'healthy' ? 'bg-green-500' : 
                          provider.status === 'unhealthy' ? 'bg-red-500' : 'bg-gray-400'
                        }`} />
                        <span className="font-medium">{provider.name}</span>
                        <Badge variant={provider.enabled ? 'default' : 'secondary'}>
                          {provider.enabled ? 'Enabled' : 'Disabled'}
                        </Badge>
                      </div>
                      <div className="text-sm text-muted-foreground">
                        {provider.models.length} models
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>

            {/* Token Usage */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <Database className="h-5 w-5" />
                  Token Usage
                </CardTitle>
                <CardDescription>Total tokens processed today</CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  <div>
                    <div className="flex justify-between text-sm mb-2">
                      <span>Tokens Used</span>
                      <span>{metrics?.total_tokens_used.toLocaleString()}</span>
                    </div>
                    <Progress value={75} className="h-2" />
                    <div className="text-xs text-muted-foreground mt-1">
                      75% of daily quota used
                    </div>
                  </div>
                  
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <div className="font-medium">Input Tokens</div>
                      <div className="text-muted-foreground">
                        {Math.floor((metrics?.total_tokens_used || 0) * 0.7).toLocaleString()}
                      </div>
                    </div>
                    <div>
                      <div className="font-medium">Output Tokens</div>
                      <div className="text-muted-foreground">
                        {Math.floor((metrics?.total_tokens_used || 0) * 0.3).toLocaleString()}
                      </div>
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          </div>
        </TabsContent>

        <TabsContent value="providers" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Provider Performance</CardTitle>
              <CardDescription>Detailed performance metrics for each provider</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {metrics?.provider_performance.map(provider => (
                  <div key={provider.provider_id} className="flex items-center justify-between p-4 border rounded-lg">
                    <div className="flex items-center gap-4">
                      <div className={`w-3 h-3 rounded-full ${
                        provider.status === 'healthy' ? 'bg-green-500' : 
                        provider.status === 'unhealthy' ? 'bg-red-500' : 'bg-gray-400'
                      }`} />
                      <div>
                        <div className="font-medium">{provider.provider_name}</div>
                        <div className="text-sm text-muted-foreground">
                          {provider.requests.toLocaleString()} requests
                        </div>
                      </div>
                    </div>
                    <div className="flex items-center gap-6 text-sm">
                      <div>
                        <div className="font-medium">{provider.success_rate.toFixed(1)}%</div>
                        <div className="text-muted-foreground">Success rate</div>
                      </div>
                      <div>
                        <div className="font-medium">{Math.round(provider.avg_response_time)}ms</div>
                        <div className="text-muted-foreground">Avg response</div>
                      </div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="models" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Top Performing Models</CardTitle>
              <CardDescription>Most used models and their performance metrics</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {metrics?.top_models.map((model, index) => (
                  <div key={model.model_id} className="flex items-center justify-between p-4 border rounded-lg">
                    <div className="flex items-center gap-4">
                      <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary/10 text-primary font-bold">
                        #{index + 1}
                      </div>
                      <div>
                        <div className="font-medium">{model.model_name}</div>
                        <div className="text-sm text-muted-foreground">
                          {model.usage_count.toLocaleString()} uses
                        </div>
                      </div>
                    </div>
                    <div className="text-right">
                      <div className="font-medium">{model.success_rate.toFixed(1)}%</div>
                      <div className="text-sm text-muted-foreground">Success rate</div>
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>

        <TabsContent value="usage" className="space-y-4">
          <Card>
            <CardHeader>
              <CardTitle>Hourly Usage Pattern</CardTitle>
              <CardDescription>Request volume over the last 24 hours</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {metrics?.hourly_usage.slice(-8).map(hour => (
                  <div key={hour.hour} className="flex items-center gap-4">
                    <div className="w-12 text-sm text-muted-foreground">
                      {hour.hour}
                    </div>
                    <div className="flex-1">
                      <div className="flex justify-between text-sm mb-1">
                        <span>{hour.requests} requests</span>
                        <span>{hour.tokens.toLocaleString()} tokens</span>
                      </div>
                      <Progress value={(hour.requests / 500) * 100} className="h-2" />
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  )
}