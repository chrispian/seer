import React from 'react'
import { ScrollArea } from '@/components/ui/scroll-area'
import { 
  TodayActivityWidget, 
  RecentBookmarksWidget, 
  SessionInfoWidget, 
  ToolCallsWidget 
} from '@/widgets'

export function RightRail() {
  return (
    <div className="w-80 bg-white border-l flex flex-col overflow-hidden">
      <ScrollArea className="flex-1">
        <div className="px-1 py-1 space-y-2 overflow-hidden">
          {/* Today's Activity Widget */}
          <TodayActivityWidget />

          {/* Recent Bookmarks Widget */}
          <RecentBookmarksWidget />

          {/* Tool Calls & Reasoning Widget */}
          <ToolCallsWidget />
        </div>
      </ScrollArea>

      {/* Session Info Widget - Pinned to bottom */}
      <div className="px-1 py-1 border-t border-gray-200">
        <SessionInfoWidget />
      </div>
    </div>
  )
}
