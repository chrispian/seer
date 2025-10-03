// Centralized exports for all widgets
export { TodayActivityWidget } from './today-activity/TodayActivityWidget'
export { RecentBookmarksWidget } from './recent-bookmarks/RecentBookmarksWidget'
export { SessionInfoWidget } from './session-info/SessionInfoWidget'
export { ToolCallsWidget } from './tool-calls/ToolCallsWidget'
export { InboxWidget } from './inbox/InboxWidget'
export { TypeSystemWidget } from './type-system/TypeSystemWidget'
export { SchedulerWidget } from './scheduler/SchedulerWidget'

// Widget types
export type { TodayActivityData } from './today-activity/types'
export type { BookmarkData } from './recent-bookmarks/types'
export type { SessionInfoData } from './session-info/types'
export type { ToolCallData } from './tool-calls/types'
export type { InboxFragment, InboxStats, InboxFilters } from './inbox/hooks/useInbox'
export type { TypeInfo, TypeStats, TypeSystemStats } from './type-system/hooks/useTypes'
export type { Schedule, ScheduleRun, SchedulerStats } from './scheduler/hooks/useScheduler'