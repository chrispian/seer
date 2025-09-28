export interface TodayActivityData {
  messages: number
  commands: number
  totalTokensIn: number
  totalTokensOut: number
  totalCost: number
  avgResponseTime: number
  modelsUsed: string[]
  chartData: {
    hour: string
    messages: number
    tokens: number
    cost: number
  }[]
}

export interface TodayActivityMetrics {
  period: 'today' | '7days' | '30days'
  data: TodayActivityData
  isLoading: boolean
  error?: string
}