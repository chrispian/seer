# Tier 3B Form Components

Advanced form components for UI Builder v2 (9 components total).

## Components

### 1. Form
Validation framework wrapper with React Hook Form integration.

**Props:**
- `fields` - Array of form fields with validation
- `submitButton` - Optional submit button
- `onSubmit` - Submit action config

**Actions:**
- `submit` - Triggered on form submission

### 2. InputGroup
Input with prefix/suffix (text or components).

**Props:**
- `prefix` - String or component to show before input
- `suffix` - String or component to show after input
- `input` - The input component

### 3. InputOTP
OTP code input (6-digit by default).

**Props:**
- `length` - Number of digits (default: 6)

**Actions:**
- `complete` - Triggered when all digits entered

### 4. DatePicker
Date selection with calendar popover.

**Props:**
- `value` - ISO date string
- `placeholder` - Placeholder text
- `format` - Display format (default: PPP)
- `disabled` - Disable input

**Actions:**
- `change` - Triggered on date selection

### 5. Calendar
Month calendar view with single/multiple/range modes.

**Props:**
- `value` - Selected date(s)
- `mode` - Selection mode (single/multiple/range)

**Actions:**
- `change` - Triggered on date change

### 6. ButtonGroup
Grouped buttons (segmented control).

**Props:**
- `buttons` - Array of buttons with value, label, icon
- `value` - Selected button value

**Actions:**
- `change` - Triggered on selection change

### 7. Toggle
Toggle button (pressed/not pressed).

**Props:**
- `pressed` - Initial pressed state
- `label` - Button label
- `icon` - Icon name
- `variant` - Style variant (default/outline)
- `size` - Size (default/sm/lg)
- `disabled` - Disable toggle

**Actions:**
- `change` - Triggered on press change

### 8. ToggleGroup
Multiple toggles with single or multi-select.

**Props:**
- `type` - Selection type (single/multiple)
- `items` - Array of toggle items
- `value` - Selected value(s)
- `variant` - Style variant
- `size` - Size

**Actions:**
- `change` - Triggered on selection change

### 9. Item
Generic list item (for lists/menus).

**Props:**
- `title` - Item title
- `description` - Optional description
- `icon` - Optional icon name
- `avatar` - Optional avatar URL
- `badge` - Optional badge text
- `trailing` - Optional trailing component

**Actions:**
- `click` - Triggered on item click

## Usage

```typescript
import { registerFormComponents } from '@/components/v2';

registerFormComponents();
```

## Examples

See `examples.ts` for complete examples of each component.
