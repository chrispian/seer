import React from 'react';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuItem,
  SidebarMenuButton,
  SidebarMenuSub,
  SidebarMenuSubItem,
  SidebarMenuSubButton,
  SidebarMenuBadge,
  SidebarProvider,
} from '@/components/ui/sidebar';
import { ChevronRight } from 'lucide-react';
import * as Icons from 'lucide-react';

export interface SidebarConfig extends ComponentConfig {
  type: 'sidebar';
  props: {
    collapsible?: boolean;
    defaultOpen?: boolean;
    side?: 'left' | 'right';
    variant?: 'sidebar' | 'floating' | 'inset';
    items: Array<{
      label: string;
      icon?: string;
      href?: string;
      badge?: string;
      active?: boolean;
      children?: Array<{
        label: string;
        href: string;
        active?: boolean;
      }>;
    }>;
    groups?: Array<{
      label: string;
      items: Array<{
        label: string;
        icon?: string;
        href?: string;
        badge?: string;
        active?: boolean;
        children?: Array<{
          label: string;
          href: string;
          active?: boolean;
        }>;
      }>;
    }>;
    className?: string;
  };
}

function getIcon(iconName?: string) {
  if (!iconName) return null;
  const Icon = (Icons as any)[iconName];
  return Icon ? <Icon className="h-4 w-4" /> : null;
}

export function SidebarComponent({ config }: { config: SidebarConfig }) {
  const { props } = config;
  const {
    collapsible = true,
    defaultOpen = true,
    side = 'left',
    variant = 'sidebar',
    items = [],
    groups = [],
    className,
  } = props;

  const [expandedItems, setExpandedItems] = React.useState<Set<string>>(new Set());

  const toggleItem = (label: string) => {
    setExpandedItems((prev) => {
      const next = new Set(prev);
      if (next.has(label)) {
        next.delete(label);
      } else {
        next.add(label);
      }
      return next;
    });
  };

  const renderMenuItem = (item: any, index: number) => {
    const hasChildren = item.children && item.children.length > 0;
    const isExpanded = expandedItems.has(item.label);

    return (
      <SidebarMenuItem key={index}>
        <SidebarMenuButton
          asChild={!hasChildren}
          isActive={item.active}
          onClick={hasChildren ? () => toggleItem(item.label) : undefined}
        >
          {hasChildren ? (
            <div className="flex items-center justify-between w-full">
              <div className="flex items-center gap-2">
                {getIcon(item.icon)}
                <span>{item.label}</span>
              </div>
              <ChevronRight
                className={cn(
                  'h-4 w-4 transition-transform',
                  isExpanded && 'rotate-90'
                )}
              />
            </div>
          ) : (
            <a href={item.href || '#'}>
              {getIcon(item.icon)}
              <span>{item.label}</span>
              {item.badge && <SidebarMenuBadge>{item.badge}</SidebarMenuBadge>}
            </a>
          )}
        </SidebarMenuButton>
        {hasChildren && isExpanded && (
          <SidebarMenuSub>
            {item.children.map((child: any, childIndex: number) => (
              <SidebarMenuSubItem key={childIndex}>
                <SidebarMenuSubButton asChild isActive={child.active}>
                  <a href={child.href || '#'}>
                    <span>{child.label}</span>
                  </a>
                </SidebarMenuSubButton>
              </SidebarMenuSubItem>
            ))}
          </SidebarMenuSub>
        )}
      </SidebarMenuItem>
    );
  };

  return (
    <SidebarProvider defaultOpen={defaultOpen}>
      <Sidebar
        side={side}
        variant={variant}
        collapsible={collapsible ? 'icon' : 'none'}
        className={cn(className)}
      >
        <SidebarContent>
          {groups.length > 0 ? (
            groups.map((group, groupIndex) => (
              <SidebarGroup key={groupIndex}>
                {group.label && <SidebarGroupLabel>{group.label}</SidebarGroupLabel>}
                <SidebarGroupContent>
                  <SidebarMenu>
                    {group.items.map((item, itemIndex) => renderMenuItem(item, itemIndex))}
                  </SidebarMenu>
                </SidebarGroupContent>
              </SidebarGroup>
            ))
          ) : (
            <SidebarGroup>
              <SidebarGroupContent>
                <SidebarMenu>
                  {items.map((item, index) => renderMenuItem(item, index))}
                </SidebarMenu>
              </SidebarGroupContent>
            </SidebarGroup>
          )}
        </SidebarContent>
      </Sidebar>
    </SidebarProvider>
  );
}
