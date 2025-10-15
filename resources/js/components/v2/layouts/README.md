# Layout Components

Config-driven structural/layout components for UI Builder v2.

## Components

### Card
Container with header, body, and footer sections.

```typescript
{
  type: 'card',
  props: { title, description, footer, className },
  children: [...]
}
```

### ScrollArea
Scrollable container with custom scrollbar.

```typescript
{
  type: 'scroll-area',
  props: { height, maxHeight, className },
  children: [...]
}
```

### Resizable
Split panels with draggable dividers.

```typescript
{
  type: 'resizable',
  props: {
    direction: 'horizontal',
    panels: [
      { id, defaultSize, minSize, maxSize, content: [...] }
    ]
  }
}
```

### AspectRatio
Maintains aspect ratio wrapper.

```typescript
{
  type: 'aspect-ratio',
  props: { ratio: '16/9', className },
  children: [...]
}
```

### Collapsible
Single expandable/collapsible section.

```typescript
{
  type: 'collapsible',
  props: { title, defaultOpen, disabled, className },
  children: [...]
}
```

### Accordion
Multiple collapsible sections.

```typescript
{
  type: 'accordion',
  props: {
    type: 'single', // or 'multiple'
    collapsible: true,
    items: [
      { value, title, content: [...] }
    ]
  }
}
```

## Usage

```typescript
import { registerLayoutComponents } from '@/components/v2';

// Register all layout components
registerLayoutComponents();
```

## Examples

See `examples.ts` for comprehensive usage examples.
