import React from 'react';
import { ComponentConfig } from './types';

type ComponentRenderer = React.ComponentType<{ config: ComponentConfig }>;

class ComponentRegistry {
  private components: Map<string, ComponentRenderer> = new Map();

  register(type: string, component: ComponentRenderer): void {
    this.components.set(type, component);
  }

  get(type: string): ComponentRenderer | undefined {
    return this.components.get(type);
  }

  has(type: string): boolean {
    return this.components.has(type);
  }

  getAll(): string[] {
    return Array.from(this.components.keys());
  }
}

export const registry = new ComponentRegistry();
export { registry as componentRegistry };

export function registerPrimitiveComponents() {
  import('./primitives/ButtonComponent').then(({ ButtonComponent }) => {
    registry.register('button', ButtonComponent as ComponentRenderer);
    registry.register('button.icon', ButtonComponent as ComponentRenderer);
    registry.register('button.text', ButtonComponent as ComponentRenderer);
  });

  import('./primitives/InputComponent').then(({ InputComponent }) => {
    registry.register('input', InputComponent as ComponentRenderer);
    registry.register('input.text', InputComponent as ComponentRenderer);
    registry.register('input.email', InputComponent as ComponentRenderer);
    registry.register('input.password', InputComponent as ComponentRenderer);
    registry.register('input.number', InputComponent as ComponentRenderer);
  });

  import('./primitives/LabelComponent').then(({ LabelComponent }) => {
    registry.register('label', LabelComponent as ComponentRenderer);
  });

  import('./primitives/BadgeComponent').then(({ BadgeComponent }) => {
    registry.register('badge', BadgeComponent as ComponentRenderer);
  });

  import('./primitives/AvatarComponent').then(({ AvatarComponent }) => {
    registry.register('avatar', AvatarComponent as ComponentRenderer);
  });

  import('./primitives/SkeletonComponent').then(({ SkeletonComponent }) => {
    registry.register('skeleton', SkeletonComponent as ComponentRenderer);
  });

  import('./primitives/SpinnerComponent').then(({ SpinnerComponent }) => {
    registry.register('spinner', SpinnerComponent as ComponentRenderer);
  });

  import('./primitives/SeparatorComponent').then(({ SeparatorComponent }) => {
    registry.register('separator', SeparatorComponent as ComponentRenderer);
  });

  import('./primitives/KbdComponent').then(({ KbdComponent }) => {
    registry.register('kbd', KbdComponent as ComponentRenderer);
  });

  import('./primitives/TypographyComponent').then(({ TypographyComponent }) => {
    registry.register('typography', TypographyComponent as ComponentRenderer);
    registry.register('typography.h1', TypographyComponent as ComponentRenderer);
    registry.register('typography.h2', TypographyComponent as ComponentRenderer);
    registry.register('typography.h3', TypographyComponent as ComponentRenderer);
    registry.register('typography.h4', TypographyComponent as ComponentRenderer);
    registry.register('typography.h5', TypographyComponent as ComponentRenderer);
    registry.register('typography.h6', TypographyComponent as ComponentRenderer);
    registry.register('typography.p', TypographyComponent as ComponentRenderer);
    registry.register('typography.blockquote', TypographyComponent as ComponentRenderer);
    registry.register('typography.code', TypographyComponent as ComponentRenderer);
    registry.register('typography.lead', TypographyComponent as ComponentRenderer);
    registry.register('typography.large', TypographyComponent as ComponentRenderer);
    registry.register('typography.small', TypographyComponent as ComponentRenderer);
    registry.register('typography.muted', TypographyComponent as ComponentRenderer);
  });

  import('./primitives/CheckboxComponent').then(({ CheckboxComponent }) => {
    registry.register('checkbox', CheckboxComponent as ComponentRenderer);
  });

  import('./primitives/RadioGroupComponent').then(({ RadioGroupComponent }) => {
    registry.register('radio-group', RadioGroupComponent as ComponentRenderer);
  });

  import('./primitives/SwitchComponent').then(({ SwitchComponent }) => {
    registry.register('switch', SwitchComponent as ComponentRenderer);
  });

  import('./primitives/SliderComponent').then(({ SliderComponent }) => {
    registry.register('slider', SliderComponent as ComponentRenderer);
  });

  import('./primitives/TextareaComponent').then(({ TextareaComponent }) => {
    registry.register('textarea', TextareaComponent as ComponentRenderer);
  });

  import('./primitives/SelectComponent').then(({ SelectComponent }) => {
    registry.register('select', SelectComponent as ComponentRenderer);
  });

  import('./primitives/FieldComponent').then(({ FieldComponent }) => {
    registry.register('field', FieldComponent as ComponentRenderer);
  });

  import('./primitives/AlertComponent').then(({ AlertComponent }) => {
    registry.register('alert', AlertComponent as ComponentRenderer);
  });

  import('./primitives/ProgressComponent').then(({ ProgressComponent }) => {
    registry.register('progress', ProgressComponent as ComponentRenderer);
  });

  import('./primitives/ToastComponent').then(({ ToastComponent }) => {
    registry.register('toast', ToastComponent as ComponentRenderer);
  });

  import('./primitives/EmptyComponent').then(({ EmptyComponent }) => {
    registry.register('empty', EmptyComponent as ComponentRenderer);
  });
}

export function registerLayoutComponents() {
  import('./layouts/CardComponent').then(({ CardComponent }) => {
    registry.register('card', CardComponent as ComponentRenderer);
  });

  import('./layouts/ScrollAreaComponent').then(({ ScrollAreaComponent }) => {
    registry.register('scroll-area', ScrollAreaComponent as ComponentRenderer);
  });

  import('./layouts/ResizableComponent').then(({ ResizableComponent }) => {
    registry.register('resizable', ResizableComponent as ComponentRenderer);
  });

  import('./layouts/AspectRatioComponent').then(({ AspectRatioComponent }) => {
    registry.register('aspect-ratio', AspectRatioComponent as ComponentRenderer);
  });

  import('./layouts/CollapsibleComponent').then(({ CollapsibleComponent }) => {
    registry.register('collapsible', CollapsibleComponent as ComponentRenderer);
  });

  import('./layouts/AccordionComponent').then(({ AccordionComponent }) => {
    registry.register('accordion', AccordionComponent as ComponentRenderer);
  });

  import('./layouts/RowsLayout').then(({ RowsLayout }) => {
    registry.register('rows', RowsLayout as ComponentRenderer);
  });

  import('./layouts/ColumnsLayout').then(({ ColumnsLayout }) => {
    registry.register('columns', ColumnsLayout as ComponentRenderer);
  });
}

export function registerNavigationComponents() {
  import('./navigation/TabsComponent').then(({ TabsComponent }) => {
    registry.register('tabs', TabsComponent as ComponentRenderer);
  });

  import('./navigation/BreadcrumbComponent').then(({ BreadcrumbComponent }) => {
    registry.register('breadcrumb', BreadcrumbComponent as ComponentRenderer);
  });

  import('./navigation/PaginationComponent').then(({ PaginationComponent }) => {
    registry.register('pagination', PaginationComponent as ComponentRenderer);
  });

  import('./navigation/SidebarComponent').then(({ SidebarComponent }) => {
    registry.register('sidebar', SidebarComponent as ComponentRenderer);
  });
}

export function registerCompositeComponents() {
  import('./composites/DialogComponent').then(({ DialogComponent }) => {
    registry.register('dialog', DialogComponent as ComponentRenderer);
  });

  import('./composites/PopoverComponent').then(({ PopoverComponent }) => {
    registry.register('popover', PopoverComponent as ComponentRenderer);
  });

  import('./composites/TooltipComponent').then(({ TooltipComponent }) => {
    registry.register('tooltip', TooltipComponent as ComponentRenderer);
  });

  import('./composites/SheetComponent').then(({ SheetComponent }) => {
    registry.register('sheet', SheetComponent as ComponentRenderer);
  });

  import('./composites/DrawerComponent').then(({ DrawerComponent }) => {
    registry.register('drawer', DrawerComponent as ComponentRenderer);
  });

  import('./composites/NavigationMenuComponent').then(({ NavigationMenuComponent }) => {
    registry.register('navigation-menu', NavigationMenuComponent as ComponentRenderer);
  });

  import('./composites/CommandComponent').then(({ CommandComponent }) => {
    registry.register('command', CommandComponent as ComponentRenderer);
  });

  import('./composites/ComboboxComponent').then(({ ComboboxComponent }) => {
    registry.register('combobox', ComboboxComponent as ComponentRenderer);
  });

  import('./composites/DropdownMenuComponent').then(({ DropdownMenuComponent }) => {
    registry.register('dropdown-menu', DropdownMenuComponent as ComponentRenderer);
  });

  import('./composites/ContextMenuComponent').then(({ ContextMenuComponent }) => {
    registry.register('context-menu', ContextMenuComponent as ComponentRenderer);
  });

  import('./composites/MenubarComponent').then(({ MenubarComponent }) => {
    registry.register('menubar', MenubarComponent as ComponentRenderer);
  });

  import('./composites/HoverCardComponent').then(({ HoverCardComponent }) => {
    registry.register('hover-card', HoverCardComponent as ComponentRenderer);
  });

  import('./composites/SearchBarComponent').then(({ SearchBarComponent }) => {
    registry.register('search.bar', SearchBarComponent as ComponentRenderer);
  });
}

export function registerAdvancedComponents() {
  import('./advanced/DataTableComponent').then(({ DataTableComponent }) => {
    registry.register('data-table', DataTableComponent as ComponentRenderer);
  });

  import('./advanced/ChartComponent').then(({ ChartComponent }) => {
    registry.register('chart', ChartComponent as ComponentRenderer);
  });

  import('./advanced/CarouselComponent').then(({ CarouselComponent }) => {
    registry.register('carousel', CarouselComponent as ComponentRenderer);
  });

  import('./advanced/SonnerComponent').then(({ SonnerComponent }) => {
    registry.register('sonner', SonnerComponent as ComponentRenderer);
  });
}

export function registerFormComponents() {
  import('./forms/FormComponent').then(({ FormComponent }) => {
    registry.register('form', FormComponent as ComponentRenderer);
  });

  import('./forms/InputGroupComponent').then(({ InputGroupComponent }) => {
    registry.register('input-group', InputGroupComponent as ComponentRenderer);
  });

  import('./forms/InputOTPComponent').then(({ InputOTPComponent }) => {
    registry.register('input-otp', InputOTPComponent as ComponentRenderer);
  });

  import('./forms/DatePickerComponent').then(({ DatePickerComponent }) => {
    registry.register('date-picker', DatePickerComponent as ComponentRenderer);
  });

  import('./forms/CalendarComponent').then(({ CalendarComponent }) => {
    registry.register('calendar', CalendarComponent as ComponentRenderer);
  });

  import('./forms/ButtonGroupComponent').then(({ ButtonGroupComponent }) => {
    registry.register('button-group', ButtonGroupComponent as ComponentRenderer);
  });

  import('./forms/ToggleComponent').then(({ ToggleComponent }) => {
    registry.register('toggle', ToggleComponent as ComponentRenderer);
  });

  import('./forms/ToggleGroupComponent').then(({ ToggleGroupComponent }) => {
    registry.register('toggle-group', ToggleGroupComponent as ComponentRenderer);
  });

  import('./forms/ItemComponent').then(({ ItemComponent }) => {
    registry.register('item', ItemComponent as ComponentRenderer);
  });
}

export function renderComponent(config: ComponentConfig): React.ReactElement | null {
  const Component = registry.get(config.type);
  if (!Component) {
    console.warn(`Component type "${config.type}" not found in registry`);
    return null;
  }
  return React.createElement(Component, { config });
}
