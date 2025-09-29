export interface TodayActivityData {
  messages: number
  commands: number
  totalTokensIn: number
  totalTokensOut: number
  totalCost: number
  avgResponseTime: number
  modelsUsed: string[]
  providersUsed: string[]
  chartData: {
    hour: string
    messages: number
    commands: number
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