# Responsive Adaptation Context

## Current Responsive State
- Basic responsive design with Tailwind breakpoints
- Fixed-width RightRail (w-80) that doesn't adapt well to mobile
- AppSidebar with basic responsive behavior
- Chat interface with minimal mobile optimization
- Limited use of container queries

## Target Responsive Enhancements
- Comprehensive breakpoint coverage (sm, md, lg, xl, 2xl)
- Container queries for component-level responsiveness
- Mobile-first responsive design principles
- Tablet-specific layout optimizations
- Touch-optimized interactions
- Performance optimization for smaller devices

## Key Responsive Challenges
- RightRail fixed width causes horizontal scroll on mobile
- Sidebar needs better mobile overlay/drawer behavior
- Widget containers need mobile-optimized layouts
- Chat interface needs mobile touch optimization
- Navigation needs mobile-friendly interactions

## Breakpoint Strategy
- **Mobile (< 768px)**: Single column, stacked widgets, overlay sidebar
- **Tablet (768px - 1024px)**: Hybrid layout, collapsible sidebar, grid widgets
- **Desktop (> 1024px)**: Full layout with all panels, enhanced spacing

## Container Query Opportunities
- Widget containers for internal layout adaptation
- Chat interface for message sizing
- Sidebar for internal content organization
- Card components for content density adaptation

## Technical Requirements
- Tailwind v4 compatibility for container queries
- Performance optimization for mobile devices
- Touch interaction enhancement
- Accessibility preservation across breakpoints
- Smooth transitions between breakpoints

## Device Testing Requirements
- iPhone (various sizes): 375px, 390px, 414px, 428px
- Android phones: 360px, 393px, 412px
- Tablets: 768px, 820px, 1024px
- Desktop: 1280px, 1440px, 1920px+

## Performance Considerations
- Mobile performance optimization
- Reduced JavaScript execution on smaller devices
- Optimized image and asset loading
- Efficient CSS for responsive layouts
- Smooth animations and transitions

## Accessibility Requirements
- Touch targets minimum 44px
- Keyboard navigation across all breakpoints
- Screen reader compatibility
- Focus management during responsive changes
- Proper heading hierarchy maintenance