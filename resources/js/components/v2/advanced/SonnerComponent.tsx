import { toast } from 'sonner';
import { cn } from '@/lib/utils';
import { ComponentConfig, ActionConfig } from '../types';

interface SonnerConfig extends ComponentConfig {
  type: 'sonner';
  props: {
    message: string;
    description?: string;
    action?: {
      label: string;
      action: ActionConfig;
    };
    duration?: number;
    position?: 'top-left' | 'top-center' | 'top-right' | 'bottom-left' | 'bottom-center' | 'bottom-right';
    variant?: 'default' | 'success' | 'error' | 'warning' | 'info';
    className?: string;
  };
}

function executeAction(action: ActionConfig) {
  const { type, command, url, event: eventName, payload } = action;

  if (type === 'command' && command) {
    window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload } }));
  } else if (type === 'navigate' && url) {
    window.location.href = url;
  } else if (type === 'emit' && eventName) {
    window.dispatchEvent(new CustomEvent(eventName, { detail: payload }));
  } else if (type === 'http' && url) {
    fetch(url, {
      method: action.method || 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    }).catch(console.error);
  }
}

export function SonnerComponent({ config }: { config: SonnerConfig }) {
  const { props } = config;
  const {
    message,
    description,
    action: actionConfig,
    duration = 4000,
    variant = 'default',
    className,
  } = props;

  const showToast = () => {
    const toastOptions = {
      description,
      duration,
      className: cn(className),
      action: actionConfig ? {
        label: actionConfig.label,
        onClick: () => executeAction(actionConfig.action),
      } : undefined,
    };

    switch (variant) {
      case 'success':
        toast.success(message, toastOptions);
        break;
      case 'error':
        toast.error(message, toastOptions);
        break;
      case 'warning':
        toast.warning(message, toastOptions);
        break;
      case 'info':
        toast.info(message, toastOptions);
        break;
      default:
        toast(message, toastOptions);
    }
  };

  showToast();

  return null;
}
