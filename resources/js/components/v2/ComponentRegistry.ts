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

export function renderComponent(config: ComponentConfig): React.ReactElement | null {
  const Component = registry.get(config.type);
  if (!Component) {
    console.warn(`Component type "${config.type}" not found in registry`);
    return null;
  }
  return React.createElement(Component, { config });
}
