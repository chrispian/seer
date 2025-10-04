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
    <aside className="w-80 lg:w-96 bg-background border-l border-border flex flex-col overflow-hidden">
      {/* Main Widgets Area */}
      <ScrollArea className="flex-1">
        <div className="p-4 lg:p-6">
          {/* Widget Container Grid */}
          <div className="grid grid-cols-1 gap-4 lg:gap-6">
            {/* Primary Widgets Section */}
            <div className="space-y-4">
              <div className="grid grid-cols-1 gap-4">
                {/* Today's Activity Widget */}
                <div className="widget-container">
                  <TodayActivityWidget />
                </div>

                {/* Recent Bookmarks Widget */}
                <div className="widget-container">
                  <RecentBookmarksWidget />
                </div>

                {/* Tool Calls & Reasoning Widget */}
                <div className="widget-container">
                  <ToolCallsWidget />
                </div>
              </div>
            </div>
          </div>
        </div>
      </ScrollArea>

      {/* Session Info Widget - Pinned Footer */}
      <footer className="p-4 lg:p-6 border-t border-border bg-muted/30">
        <div className="widget-container">
          <SessionInfoWidget />
        </div>
      </footer>
    </aside>
  )
}
