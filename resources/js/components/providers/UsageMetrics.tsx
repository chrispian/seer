import React, { useState, useEffect } from 'react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Progress } from '@/components/ui/progress'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Alert, AlertDescription } from '@/components/ui/alert'
import { LoadingSpinner } from '@/components/ui/loading-spinner'
import { 
  DollarSign, 
  TrendingUp, 
  TrendingDown, 
  Calendar,
  BarChart3,
  PieChart,
  Download,
  Filter,
  AlertTriangle,
  Clock,
  Zap
} from 'lucide-react'
import type { Provider } from '@/types/provider'

interface CostBreakdown {
  provider_id: string
  provider_name: string
  total_cost: number
  input_tokens_cost: number
  output_tokens_cost: number
  requests_count: number
  tokens_used: number
  cost_per_request: number
  cost_per_1k_tokens: number
}

interface UsageTimeframe {
  period: string
  start_date: string
  end_date: string
  total_requests: number
  total_tokens: number
  total_cost: number
  avg_response_time: number
  success_rate: number
}

interface UsageMetricsProps {
  providers: Provider[]
  selectedProviderId?: string
  onProviderChange?: (providerId: string) => void
}

export function UsageMetrics({ 
  providers, 
  selectedProviderId,
  onProviderChange 
}: UsageMetricsProps) {
  const [timeframe, setTimeframe] = useState<'1d' | '7d' | '30d' | '90d'>('7d')
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [costBreakdown, setCostBreakdown] = useState<CostBreakdown[]>([])
  const [usageHistory, setUsageHistory] = useState<UsageTimeframe[]>([])

  // Mock data for demonstration
  useEffect(() => {
    const fetchUsageData = async () => {
      setLoading(true)
      setError(null)
      
      try {
        // Simulate API delay
        await new Promise(resolve => setTimeout(resolve, 500))
        
        // Mock cost breakdown data
        const mockCostData: CostBreakdown[] = providers.map(provider => ({
          provider_id: provider.id,
          provider_name: provider.name,
          total_cost: Math.random() * 100 + 20,
          input_tokens_cost: Math.random() * 40 + 10,
          output_tokens_cost: Math.random() * 60 + 10,
          requests_count: Math.floor(Math.random() * 1000) + 100,
          tokens_used: Math.floor(Math.random() * 100000) + 10000,
          cost_per_request: 0,
          cost_per_1k_tokens: 0
        })).map(item => ({
          ...item,
          cost_per_request: item.total_cost / item.requests_count,
          cost_per_1k_tokens: (item.total_cost / item.tokens_used) * 1000
        }))
        
        // Mock usage history
        const periods = timeframe === '1d' ? 24 : timeframe === '7d' ? 7 : timeframe === '30d' ? 30 : 90
        const mockHistoryData: UsageTimeframe[] = Array.from({ length: periods }, (_, i) => {
          const date = new Date()
          date.setDate(date.getDate() - (periods - 1 - i))
          return {
            period: timeframe === '1d' ? `${i}:00` : date.toISOString().split('T')[0],
            start_date: date.toISOString(),
            end_date: date.toISOString(),
            total_requests: Math.floor(Math.random() * 500) + 50,
            total_tokens: Math.floor(Math.random() * 50000) + 5000,
            total_cost: Math.random() * 20 + 2,
            avg_response_time: Math.random() * 1000 + 500,
            success_rate: 95 + Math.random() * 5
          }
        })
        
        setCostBreakdown(mockCostData)
        setUsageHistory(mockHistoryData)
      } catch (err) {
        setError('Failed to load usage metrics')
      } finally {
        setLoading(false)
      }
    }
    
    fetchUsageData()
  }, [providers, timeframe, selectedProviderId])

  // Calculate totals
  const totalCost = costBreakdown.reduce((sum, item) => sum + item.total_cost, 0)
  const totalRequests = costBreakdown.reduce((sum, item) => sum + item.requests_count, 0)
  const totalTokens = costBreakdown.reduce((sum, item) => sum + item.tokens_used, 0)

  // Filter data by selected provider
  const filteredCostData = selectedProviderId 
    ? costBreakdown.filter(item => item.provider_id === selectedProviderId)
    : costBreakdown

  // Get time period label
  const getTimeLabel = () => {
    switch (timeframe) {
      case '1d': return 'Last 24 Hours'
      case '7d': return 'Last 7 Days'
      case '30d': return 'Last 30 Days'
      case '90d': return 'Last 90 Days'
      default: return 'Last 7 Days'
    }
  }

  // Export data function
  const handleExport = () => {
    const exportData = {
      timeframe: getTimeLabel(),
      generated_at: new Date().toISOString(),
      summary: {
        total_cost: totalCost,
        total_requests: totalRequests,
        total_tokens: totalTokens
      },
      cost_breakdown: filteredCostData,
      usage_history: usageHistory
    }
    
    const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' })
    const url = URL.createObjectURL(blob)
    const a = document.createElement('a')
    a.href = url
    a.download = `usage-metrics-${timeframe}-${new Date().toISOString().split('T')[0]}.json`
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
    URL.revokeObjectURL(url)
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner className="h-8 w-8" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold">Usage Metrics & Cost Tracking</h2>
          <p className="text-muted-foreground">
            Monitor AI provider usage and analyze costs over time
          </p>
        </div>
        
        <div className="flex items-center gap-2">
          <Select value={timeframe} onValueChange={(value: any) => setTimeframe(value)}>
            <SelectTrigger className="w-40">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="1d">Last 24 Hours</SelectItem>
              <SelectItem value="7d">Last 7 Days</SelectItem>
              <SelectItem value="30d">Last 30 Days</SelectItem>
              <SelectItem value="90d">Last 90 Days</SelectItem>
            </SelectContent>
          </Select>
          
          {providers.length > 1 && (
            <Select value={selectedProviderId || 'all'} onValueChange={onProviderChange}>
              <SelectTrigger className="w-48">
                <SelectValue placeholder="All Providers" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Providers</SelectItem>
                {providers.map(provider => (
                  <SelectItem key={provider.id} value={provider.id}>
                    {provider.name}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}
          
          <Button 
            onClick={handleExport} 
            variant="outline" 
            size="sm"
            className="flex items-center gap-2"
          >
            <Download className="h-4 w-4" />
            Export
          </Button>
        </div>
      </div>

      {/* Summary Cards */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Cost ({getTimeLabel()})</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">${totalCost.toFixed(2)}</div>
            <div className="flex items-center gap-1 text-xs text-green-600">
              <TrendingDown className="h-3 w-3" />
              -5.2% from previous period
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Requests</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalRequests.toLocaleString()}</div>
            <div className="flex items-center gap-1 text-xs text-green-600">
              <TrendingUp className="h-3 w-3" />
              +12.8% from previous period
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Tokens Processed</CardTitle>
            <Zap className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{(totalTokens / 1000).toFixed(1)}K</div>
            <div className="flex items-center gap-1 text-xs text-green-600">
              <TrendingUp className="h-3 w-3" />
              +8.4% from previous period
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Error Alert */}
      {error && (
        <Alert variant="destructive">
          <AlertTriangle className="h-4 w-4" />
          <AlertDescription>{error}</AlertDescription>
        </Alert>
      )}

      {/* Cost Breakdown */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <PieChart className="h-5 w-5" />
            Cost Breakdown by Provider
          </CardTitle>
          <CardDescription>
            Detailed cost analysis for {selectedProviderId ? 'selected provider' : 'all providers'}
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {filteredCostData.map(provider => {
              const costPercentage = (provider.total_cost / totalCost) * 100
              
              return (
                <div key={provider.provider_id} className="space-y-2">
                  <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                      <span className="font-medium">{provider.provider_name}</span>
                      <Badge variant="outline" className="text-xs">
                        {provider.requests_count.toLocaleString()} requests
                      </Badge>
                    </div>
                    <div className="text-right">
                      <div className="font-medium">${provider.total_cost.toFixed(2)}</div>
                      <div className="text-xs text-muted-foreground">
                        {costPercentage.toFixed(1)}% of total
                      </div>
                    </div>
                  </div>
                  
                  <Progress value={costPercentage} className="h-2" />
                  
                  <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-xs text-muted-foreground">
                    <div>
                      <span className="font-medium">Input Tokens:</span>
                      <div>${provider.input_tokens_cost.toFixed(2)}</div>
                    </div>
                    <div>
                      <span className="font-medium">Output Tokens:</span>
                      <div>${provider.output_tokens_cost.toFixed(2)}</div>
                    </div>
                    <div>
                      <span className="font-medium">Cost/Request:</span>
                      <div>${provider.cost_per_request.toFixed(4)}</div>
                    </div>
                    <div>
                      <span className="font-medium">Cost/1K Tokens:</span>
                      <div>${provider.cost_per_1k_tokens.toFixed(4)}</div>
                    </div>
                  </div>
                </div>
              )
            })}
          </div>
        </CardContent>
      </Card>

      {/* Usage History */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Calendar className="h-5 w-5" />
            Usage History - {getTimeLabel()}
          </CardTitle>
          <CardDescription>
            Historical usage patterns and cost trends
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            {usageHistory.slice(-10).map((period, index) => {
              const maxRequests = Math.max(...usageHistory.map(p => p.total_requests))
              const requestPercentage = (period.total_requests / maxRequests) * 100
              
              return (
                <div key={period.period} className="space-y-2">
                  <div className="flex items-center justify-between text-sm">
                    <span className="font-medium">
                      {timeframe === '1d' ? period.period : period.period}
                    </span>
                    <div className="flex items-center gap-4 text-xs text-muted-foreground">
                      <span>{period.total_requests} requests</span>
                      <span>${period.total_cost.toFixed(2)}</span>
                      <span>{Math.round(period.avg_response_time)}ms</span>
                    </div>
                  </div>
                  <Progress value={requestPercentage} className="h-1" />
                </div>
              )
            })}
          </div>
        </CardContent>
      </Card>

      {/* Cost Optimization Tips */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <TrendingDown className="h-5 w-5" />
            Cost Optimization Tips
          </CardTitle>
          <CardDescription>
            Recommendations to reduce AI provider costs
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            <div className="flex items-start gap-3 p-3 bg-blue-50 rounded-lg">
              <div className="w-2 h-2 rounded-full bg-blue-500 mt-2" />
              <div>
                <div className="font-medium text-sm">Enable Response Caching</div>
                <div className="text-xs text-muted-foreground">
                  Cache similar requests to reduce API calls by up to 30%
                </div>
              </div>
            </div>
            
            <div className="flex items-start gap-3 p-3 bg-green-50 rounded-lg">
              <div className="w-2 h-2 rounded-full bg-green-500 mt-2" />
              <div>
                <div className="font-medium text-sm">Optimize Model Selection</div>
                <div className="text-xs text-muted-foreground">
                  Use smaller models for simple tasks - GPT-3.5 costs 90% less than GPT-4
                </div>
              </div>
            </div>
            
            <div className="flex items-start gap-3 p-3 bg-yellow-50 rounded-lg">
              <div className="w-2 h-2 rounded-full bg-yellow-500 mt-2" />
              <div>
                <div className="font-medium text-sm">Set Token Limits</div>
                <div className="text-xs text-muted-foreground">
                  Limit maximum tokens per request to prevent unexpected high costs
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  )
}