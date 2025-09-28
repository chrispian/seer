export interface BookmarkData {
  id: number
  name: string
  fragment_ids: number[]
  last_viewed_at: string
  created_at: string
  updated_at: string
  fragment_title?: string
  fragment_preview?: string
  vault_id?: number
  project_id?: number
}

export interface BookmarkFilters {
  query: string
  vault_id?: number
  project_id?: number
  limit: number
  offset: number
}

export interface BookmarkSearchResult {
  bookmarks: BookmarkData[]
  total: number
  hasMore: boolean
}