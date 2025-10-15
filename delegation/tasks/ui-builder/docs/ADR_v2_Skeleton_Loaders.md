# ADR: Use Skeleton Loaders as Default Loading State

**Status**: Accepted  
**Date**: 2025-10-15  
**Decision Makers**: PM Orchestrator, FE Core Agent  
**Context**: UI Builder v2 - Loading State UX

---

## Context

During UI Builder v2 implementation, we encountered visual flicker when components transitioned from loading state to loaded state. Initially, components showed simple text messages like "Loading..." which caused:

1. **Layout shift**: Text replaced by complex UI structure (tables, cards, etc.)
2. **Visual flicker**: Abrupt change from minimal content to full content
3. **Perceived slowness**: Empty states made loading feel slower than it actually was
4. **Inconsistent sizing**: Modal/container sizes would jump when content loaded

### Observed Behavior

**Before (Text Loading)**:
```
Modal opens → "Loading..." text (small height) → Table appears (full height) → **Flicker**
```

**After (Skeleton Loading)**:
```
Modal opens → Skeleton table (full height) → Data populates → **Smooth transition**
```

---

## Decision

**Use skeleton loaders as the default loading state for all UI Builder v2 components.**

Skeleton loaders should:
- Match the structure of the final rendered component
- Occupy the same visual space as loaded content
- Use animated pulse effect (already available in `@/components/ui/skeleton`)
- Render the component's frame/structure (headers, borders, layout) during loading

---

## Rationale

### User Experience Benefits

1. **Perceived Performance**: Skeleton loaders make the app feel faster by showing immediate visual feedback
2. **Layout Stability**: Prevents cumulative layout shift (CLS) by maintaining consistent sizing
3. **Visual Continuity**: Smooth transitions between states reduce cognitive load
4. **Progressive Disclosure**: Users see the structure before the content, building mental model

### Technical Benefits

1. **Consistent Implementation**: Reusable `Skeleton` component already exists
2. **Minimal Code Change**: Replace conditional returns with skeleton rows/cells
3. **Accessibility**: Screen readers still announce loading state
4. **Works with Config**: Skeletons can be generated from component config (columns, layout, etc.)

### Design Consistency

Follows existing Fragments Engine patterns:
- Chat messages use skeleton loaders
- Sidebar sessions use skeleton loaders  
- Project/Vault selectors use skeleton loaders

---

## Implementation Pattern

### Table Component (Implemented)

```tsx
// Before: Text loading
if (loading) {
  return <div>Loading...</div>
}

// After: Skeleton loading
<TableBody>
  {loading ? (
    Array.from({ length: 10 }).map((_, index) => (
      <TableRow key={`skeleton-${index}`}>
        {config.columns?.map((column) => (
          <TableCell key={column.key}>
            <Skeleton className="h-4 w-full" />
          </TableCell>
        ))}
      </TableRow>
    ))
  ) : (
    // ... actual data
  )}
</TableBody>
```

### General Pattern for All Components

```tsx
// 1. Render component structure (headers, containers, borders)
// 2. Replace data cells/content with <Skeleton /> during loading
// 3. Keep layout dimensions consistent (min-height, padding, etc.)
```

---

## Alternatives Considered

### 1. Text Loading Messages
**Rejected**: Causes layout shift and feels slower

```tsx
if (loading) return <div>Loading...</div>
```

**Cons**:
- Visual flicker when content loads
- Poor perceived performance
- Inconsistent sizing

### 2. Spinner/Progress Indicators
**Rejected**: Still causes layout shift, doesn't show structure

```tsx
if (loading) return <Spinner />
```

**Cons**:
- Doesn't communicate what's loading
- No indication of content structure
- Modal still resizes when content appears

### 3. Blur/Opacity on Stale Data
**Rejected**: Not applicable for initial loads, confusing for updates

```tsx
<div className={loading ? 'opacity-50' : ''}>
  {/* old data with spinner overlay */}
</div>
```

**Cons**:
- Requires initial data to exist
- Confusing when data changes vs. loading state
- Doesn't prevent flicker on first load

### 4. No Loading State (Optimistic Rendering)
**Rejected**: Can't work for initial data fetch

**Cons**:
- Only works for mutations, not queries
- Users see nothing while waiting
- Doesn't solve modal resize issue

---

## Consequences

### Positive

✅ **Improved UX**: Smoother, more professional feel  
✅ **Consistent Pattern**: Clear guideline for all future components  
✅ **Reduced Flicker**: Maintains layout stability  
✅ **Faster Perceived Load**: Users see structure immediately  
✅ **Config-Driven**: Can generate skeletons from component metadata

### Negative

⚠️ **Slightly More Code**: Each component needs skeleton implementation  
⚠️ **Skeleton Maintenance**: Structure changes require skeleton updates  
⚠️ **Initial Complexity**: Developers must think about loading states upfront

### Neutral

ℹ️ **Not Universal**: Some components (modals without content, simple buttons) may not need skeletons  
ℹ️ **Skeleton Count**: Number of skeleton rows should be configurable (default: 10 for tables)

---

## Compliance

### Component Types

| Component Type | Skeleton Required | Pattern |
|----------------|-------------------|---------|
| **Table** | ✅ Yes | Skeleton rows matching column structure |
| **List** | ✅ Yes | Skeleton cards/items matching layout |
| **Form** | ✅ Yes | Skeleton input fields, labels |
| **Card** | ✅ Yes | Skeleton text blocks, images |
| **Search Bar** | ⚠️ Optional | Render immediately, no data dependency |
| **Button** | ❌ No | Interactive immediately |
| **Modal** | ⚠️ Contextual | Use skeletons for modal *content*, not wrapper |

### Guidelines for New Components

1. **Identify data dependencies**: What needs to load?
2. **Render structure immediately**: Headers, borders, containers
3. **Replace data cells with skeletons**: Use `<Skeleton />` for text/images
4. **Match dimensions**: Skeleton should occupy same space as real content
5. **Use config metadata**: Generate skeleton from `columns`, `fields`, etc.

---

## Example: Future Card Component

```tsx
export function CardComponent({ config }: CardComponentProps) {
  const { data, loading } = useDataSource(config.dataSource)
  
  return (
    <Card className="min-h-[12rem]"> {/* Fixed height prevents flicker */}
      <CardHeader>
        <CardTitle>
          {loading ? <Skeleton className="h-6 w-48" /> : config.title}
        </CardTitle>
      </CardHeader>
      <CardContent>
        {loading ? (
          <Skeleton lines={4} />
        ) : (
          <p>{data.description}</p>
        )}
      </CardContent>
    </Card>
  )
}
```

---

## Rollout Plan

### Phase 1: Core Primitives (Complete)
- ✅ TableComponent (implemented 2025-10-15)
- ⏳ ListComponent (future)
- ⏳ FormComponent (future)

### Phase 2: Documentation
- ✅ ADR created
- ⏳ Update component README with skeleton examples
- ⏳ Add skeleton pattern to component scaffold command

### Phase 3: Enforcement
- ⏳ Add skeleton check to component PR template
- ⏳ Update integration tests to verify skeleton presence
- ⏳ Document in CONTRIBUTING.md

---

## References

- **Skeleton Component**: `resources/js/components/ui/skeleton.tsx`
- **Table Implementation**: `resources/js/components/v2/primitives/TableComponent.tsx`
- **Related Pattern**: [Existing Chat/Sidebar skeletons in main app]
- **UX Research**: [Skeleton screens improve perceived performance by ~20%]

---

## Approval

**Approved By**: PM Orchestrator  
**Implemented By**: FE Core Agent  
**Reviewed By**: User (chrispian)  
**Status**: ✅ Accepted and Implemented

---

**END ADR**
