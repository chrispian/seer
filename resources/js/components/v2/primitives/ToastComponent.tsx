import { useEffect } from 'react';
import { ToastConfig } from '../types';
import { useToast } from '@/hooks/useToast';

export function ToastComponent({ config }: { config: ToastConfig }) {
  const { props } = config;
  const {
    title,
    description,
    variant = 'default',
    duration = 5000,
  } = props;

  const toast = useToast();

  useEffect(() => {
    const typeMap = {
      default: 'info',
      destructive: 'error',
      success: 'success',
      warning: 'warning',
    } as const;

    const toastType = typeMap[variant as keyof typeof typeMap] || 'info';

    toast.addToast({
      type: toastType,
      title,
      description,
      duration,
    });
  }, [title, description, variant, duration, toast]);

  return null;
}
