export interface AutocompleteResult {
  type: string
  value: string
  display: string
  description?: string
  fragment_id?: number
  fragment_type?: string
  created_at?: string
}

export interface AutocompleteResponse {
  results: AutocompleteResult[]
}

export async function fetchCommands(query: string = ''): Promise<AutocompleteResult[]> {
  try {
    const response = await fetch(`/api/autocomplete/commands?q=${encodeURIComponent(query)}`)
    if (!response.ok) throw new Error('Failed to fetch commands')
    const data: AutocompleteResponse = await response.json()
    return data.results
  } catch (error) {
    console.error('Error fetching commands:', error)
    return []
  }
}

export async function fetchFragments(query: string = ''): Promise<AutocompleteResult[]> {
  try {
    const response = await fetch(`/api/autocomplete/fragments?q=${encodeURIComponent(query)}&limit=10`)
    if (!response.ok) throw new Error('Failed to fetch fragments')
    const data: AutocompleteResponse = await response.json()
    return data.results
  } catch (error) {
    console.error('Error fetching fragments:', error)
    return []
  }
}

export async function fetchHashtags(query: string = ''): Promise<AutocompleteResult[]> {
  // For now, return static suggestions - could be enhanced with a real endpoint later
  const staticTags = ['work', 'personal', 'urgent', 'project', 'meeting', 'todo', 'idea', 'note']
  
  const filtered = staticTags
    .filter(tag => tag.toLowerCase().includes(query.toLowerCase()))
    .map(tag => ({
      type: 'hashtag',
      value: tag,
      display: `#${tag}`,
      description: `Tag: ${tag}`
    }))
  
  return filtered
}