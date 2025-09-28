export interface ToolCallData {
  id: string
  timestamp: string
  type: 'tool_call' | 'cot_reasoning' | 'model_response'
  title: string
  summary: string
  provider: string
  model: string
  tokenUsage: {
    input: number
    output: number
  }
  cost: number
  latency: number
  metadata: {
    reasoning?: string
    tools_used?: string[]
    weights?: Record<string, number>
    confidence?: number
    [key: string]: any
  }
  isExpanded: boolean
}

export interface ToolCallFilters {
  sessionId?: number
  type?: string
  provider?: string
  limit: number
  offset: number
}