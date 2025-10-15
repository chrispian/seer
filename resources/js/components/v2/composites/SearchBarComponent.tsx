import { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import { Search } from 'lucide-react';
import { ComponentConfig } from '../types';

interface SearchBarConfig extends ComponentConfig {
  type: 'search.bar';
  dataSource?: string;
  result?: {
    target: string;
  };
  props?: {
    placeholder?: string;
  };
}

export function SearchBarComponent({ config }: { config: SearchBarConfig }) {
  const [value, setValue] = useState('');
  const placeholder = config.props?.placeholder || `Search ${config.dataSource || ''}...`;

  // Debounce search and emit event
  useEffect(() => {
    const timer = setTimeout(() => {
      if (config.result?.target) {
        window.dispatchEvent(new CustomEvent('component:search', {
          detail: {
            target: config.result.target,
            search: value
          }
        }));
      }
    }, 300);

    return () => clearTimeout(timer);
  }, [value, config.result?.target]);

  return (
    <div className="relative">
      <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
      <Input
        type="search"
        placeholder={placeholder}
        value={value}
        onChange={(e) => setValue(e.target.value)}
        className="pl-9"
      />
    </div>
  );
}
