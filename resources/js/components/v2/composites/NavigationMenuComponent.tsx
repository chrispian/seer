import React from 'react';
import {
  NavigationMenu,
  NavigationMenuContent,
  NavigationMenuItem,
  NavigationMenuLink,
  NavigationMenuList,
  NavigationMenuTrigger,
  navigationMenuTriggerStyle,
} from '@/components/ui/navigation-menu';
import { cn } from '@/lib/utils';
import { ComponentConfig } from '../types';
import { renderComponent } from '../ComponentRegistry';
import * as Icons from 'lucide-react';

export interface NavigationMenuConfig extends ComponentConfig {
  type: 'navigation-menu';
  props: {
    items: Array<{
      label: string;
      trigger?: 'hover' | 'click';
      content?: ComponentConfig[];
      href?: string;
      items?: Array<{
        label: string;
        href: string;
        description?: string;
        icon?: string;
      }>;
    }>;
    orientation?: 'horizontal' | 'vertical';
    className?: string;
  };
}

export function NavigationMenuComponent({ config }: { config: NavigationMenuConfig }) {
  const { props } = config;
  const { items, orientation = 'horizontal', className } = props;

  return (
    <NavigationMenu orientation={orientation} className={cn(className)}>
      <NavigationMenuList>
        {items.map((item, index) => (
          <NavigationMenuItem key={index}>
            {item.href && !item.content && !item.items ? (
              <NavigationMenuLink
                href={item.href}
                className={navigationMenuTriggerStyle()}
              >
                {item.label}
              </NavigationMenuLink>
            ) : (
              <>
                <NavigationMenuTrigger>{item.label}</NavigationMenuTrigger>
                <NavigationMenuContent>
                  {item.content ? (
                    <div className="grid gap-3 p-4 md:w-[400px] lg:w-[500px] lg:grid-cols-[.75fr_1fr]">
                      {item.content.map((child) => (
                        <React.Fragment key={child.id}>
                          {renderComponent(child)}
                        </React.Fragment>
                      ))}
                    </div>
                  ) : item.items ? (
                    <ul className="grid w-[400px] gap-3 p-4 md:w-[500px] md:grid-cols-2 lg:w-[600px]">
                      {item.items.map((subItem, subIndex) => {
                        const Icon = subItem.icon
                          ? (Icons as any)[subItem.icon]
                          : null;
                        
                        return (
                          <li key={subIndex}>
                            <NavigationMenuLink asChild>
                              <a
                                href={subItem.href}
                                className={cn(
                                  'block select-none space-y-1 rounded-md p-3 leading-none no-underline outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground'
                                )}
                              >
                                <div className="flex items-center gap-2">
                                  {Icon && <Icon className="h-4 w-4" />}
                                  <div className="text-sm font-medium leading-none">
                                    {subItem.label}
                                  </div>
                                </div>
                                {subItem.description && (
                                  <p className="line-clamp-2 text-sm leading-snug text-muted-foreground">
                                    {subItem.description}
                                  </p>
                                )}
                              </a>
                            </NavigationMenuLink>
                          </li>
                        );
                      })}
                    </ul>
                  ) : null}
                </NavigationMenuContent>
              </>
            )}
          </NavigationMenuItem>
        ))}
      </NavigationMenuList>
    </NavigationMenu>
  );
}
