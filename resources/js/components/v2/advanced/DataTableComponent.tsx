import React, { useEffect, useState } from 'react';
import {
  useReactTable,
  getCoreRowModel,
  getSortedRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  flexRender,
  ColumnDef,
  SortingState,
  ColumnFiltersState,
  RowSelectionState,
} from '@tanstack/react-table';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { ChevronDown, ChevronUp, MoreHorizontal, ChevronsUpDown } from 'lucide-react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ComponentConfig, ActionConfig } from '../types';

interface DataTableColumnConfig {
  key: string;
  label: string;
  sortable?: boolean;
  filterable?: boolean;
  render?: 'text' | 'badge' | 'avatar' | 'actions' | 'custom';
  width?: string;
  align?: 'left' | 'center' | 'right';
}

interface DataTableConfig extends ComponentConfig {
  type: 'data-table';
  props: {
    columns: DataTableColumnConfig[];
    data?: any[];
    dataSource?: string;
    toolbar?: ComponentConfig[];
    rowAction?: any;
    pagination?: {
      enabled: boolean;
      pageSize: number;
    };
    selection?: {
      enabled: boolean;
      type: 'single' | 'multiple';
    };
    actions?: {
      rowClick?: ActionConfig;
      rowActions?: ComponentConfig[];
    };
    loading?: boolean;
    emptyText?: string;
    className?: string;
  };
}

function executeAction(action: ActionConfig, data?: any) {
  const { type, command, url, event: eventName, payload } = action;
  const finalPayload = { ...payload, ...data };

  if (type === 'command' && command) {
    window.dispatchEvent(new CustomEvent('command:execute', { detail: { command, payload: finalPayload } }));
  } else if (type === 'navigate' && url) {
    window.location.href = url;
  } else if (type === 'emit' && eventName) {
    window.dispatchEvent(new CustomEvent(eventName, { detail: finalPayload }));
  } else if (type === 'http' && url) {
    fetch(url, {
      method: action.method || 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(finalPayload),
    }).catch(console.error);
  }
}

function renderCellValue(value: any, render: DataTableColumnConfig['render']) {
  if (value === null || value === undefined) return '—';

  switch (render) {
    case 'badge':
      return <Badge variant={value.variant || 'default'}>{value.text || value}</Badge>;
    
    case 'avatar':
      return (
        <div className="flex items-center gap-2">
          <Avatar className="h-8 w-8">
            <AvatarImage src={value.src} alt={value.alt || ''} />
            <AvatarFallback>{value.fallback || value.alt?.[0] || '?'}</AvatarFallback>
          </Avatar>
          {value.label && <span>{value.label}</span>}
        </div>
      );
    
    case 'text':
    default:
      return <span>{String(value)}</span>;
  }
}

export function DataTableComponent({ config }: { config: DataTableConfig }) {
  const { props } = config;
  const {
    columns: columnConfigs,
    data: staticData,
    dataSource,
    toolbar,
    rowAction,
    pagination = { enabled: false, pageSize: 10 },
    selection = { enabled: false, type: 'multiple' },
    actions,
    loading: propLoading = false,
    emptyText = 'No data available',
    className,
  } = props;

  const [fetchedData, setFetchedData] = useState<any[]>([]);
  const [loading, setLoading] = useState(propLoading);
  const [error, setError] = useState<string | null>(null);
  const [sorting, setSorting] = React.useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([]);
  const [rowSelection, setRowSelection] = React.useState<RowSelectionState>({});
  const [formModalOpen, setFormModalOpen] = useState(false);
  const [formModalConfig, setFormModalConfig] = useState<any>(null);
  const [formSubmitting, setFormSubmitting] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [detailModalOpen, setDetailModalOpen] = useState(false);
  const [detailModalData, setDetailModalData] = useState<any>(null);
  const [detailLoading, setDetailLoading] = useState(false);

  // Fetch data from dataSource if provided
  useEffect(() => {
    if (dataSource && !staticData) {
      setLoading(true);
      const params = new URLSearchParams();
      if (searchTerm) {
        params.append('search', searchTerm);
      }
      
      fetch(`/api/v2/ui/datasource/${dataSource}/query?${params}`)
        .then(res => res.json())
        .then(result => {
          setFetchedData(result.data || []);
          setLoading(false);
        })
        .catch(err => {
          setError(err.message);
          setLoading(false);
        });
    }
  }, [dataSource, staticData, searchTerm]);

  // Listen for search events
  useEffect(() => {
    const handleSearch = (event: CustomEvent) => {
      if (event.detail.target === config.id) {
        setSearchTerm(event.detail.search);
      }
    };

    window.addEventListener('component:search', handleSearch as EventListener);
    return () => window.removeEventListener('component:search', handleSearch as EventListener);
  }, [config.id]);

  const data = staticData || fetchedData;

  const columns = React.useMemo<ColumnDef<any>[]>(() => {
    const cols: ColumnDef<any>[] = [];

    if (selection.enabled) {
      cols.push({
        id: 'select',
        header: ({ table }) => (
          selection.type === 'multiple' ? (
            <Checkbox
              checked={table.getIsAllPageRowsSelected()}
              onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
              aria-label="Select all"
            />
          ) : null
        ),
        cell: ({ row }) => (
          <Checkbox
            checked={row.getIsSelected()}
            onCheckedChange={(value) => row.toggleSelected(!!value)}
            aria-label="Select row"
          />
        ),
        enableSorting: false,
        enableHiding: false,
        size: 40,
      });
    }

    columnConfigs.forEach((col) => {
      cols.push({
        accessorKey: col.key,
        header: ({ column }) => {
          if (col.sortable) {
            return (
              <Button
                variant="ghost"
                size="sm"
                className="-ml-3 h-8 data-[state=open]:bg-accent"
                onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}
              >
                {col.label}
                {column.getIsSorted() === 'asc' && <ChevronUp className="ml-2 h-4 w-4" />}
                {column.getIsSorted() === 'desc' && <ChevronDown className="ml-2 h-4 w-4" />}
                {!column.getIsSorted() && <ChevronsUpDown className="ml-2 h-4 w-4 opacity-50" />}
              </Button>
            );
          }
          return <span className="font-medium">{col.label}</span>;
        },
        cell: ({ row }) => {
          const value = row.getValue(col.key);
          return (
            <div style={{ textAlign: col.align || 'left' }}>
              {renderCellValue(value, col.render)}
            </div>
          );
        },
        enableSorting: col.sortable !== false,
        size: col.width ? parseInt(col.width) : undefined,
      });
    });

    if (actions?.rowActions && actions.rowActions.length > 0) {
      cols.push({
        id: 'actions',
        header: () => <span className="sr-only">Actions</span>,
        cell: ({ row }) => (
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              {actions.rowActions!.map((action, idx) => (
                <DropdownMenuItem
                  key={idx}
                  onClick={() => {
                    if (action.actions?.click) {
                      executeAction(action.actions.click, row.original);
                    }
                  }}
                >
                  {action.props?.label || `Action ${idx + 1}`}
                </DropdownMenuItem>
              ))}
            </DropdownMenuContent>
          </DropdownMenu>
        ),
        enableSorting: false,
        size: 60,
      });
    }

    return cols;
  }, [columnConfigs, selection, actions]);

  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getPaginationRowModel: pagination.enabled ? getPaginationRowModel() : undefined,
    onSortingChange: setSorting,
    onColumnFiltersChange: setColumnFilters,
    onRowSelectionChange: setRowSelection,
    state: {
      sorting,
      columnFilters,
      rowSelection,
    },
    initialState: {
      pagination: pagination.enabled ? {
        pageSize: pagination.pageSize,
      } : undefined,
    },
  });

  if (loading) {
    return (
      <div className="w-full h-64 flex items-center justify-center">
        <div className="text-muted-foreground">Loading...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="w-full h-64 flex items-center justify-center border rounded-lg">
        <div className="text-destructive">Error: {error}</div>
      </div>
    );
  }

  if (!data.length) {
    return (
      <div className="w-full h-64 flex items-center justify-center border rounded-lg">
        <div className="text-center">
          <p className="text-muted-foreground">{emptyText}</p>
        </div>
      </div>
    );
  }

  const handleFormSubmit = async (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    if (!formModalConfig) return;

    setFormSubmitting(true);
    const formData = new FormData(e.currentTarget);
    const data = Object.fromEntries(formData.entries());

    try {
      const response = await fetch(formModalConfig.submitUrl, {
        method: formModalConfig.submitMethod || 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });

      if (response.ok) {
        setFormModalOpen(false);
        // Refresh table data if refreshTarget specified
        if (formModalConfig.refreshTarget && dataSource) {
          const result = await fetch(`/api/v2/ui/datasource/${dataSource}/query`);
          const refreshedData = await result.json();
          setFetchedData(refreshedData.data || []);
        }
      } else {
        alert('Failed to submit form');
      }
    } catch (err) {
      alert('Error submitting form');
    } finally {
      setFormSubmitting(false);
    }
  };

  const handleToolbarClick = (item: ComponentConfig) => {
    const clickAction = item.actions?.click;
    if (clickAction?.type === 'modal' && clickAction?.modal === 'form') {
      setFormModalConfig(clickAction);
      setFormModalOpen(true);
    }
  };

  const renderToolbarItem = (item: ComponentConfig) => {
    if (item.type === 'button.icon') {
      return (
        <Button 
          key={item.id} 
          variant="default" 
          size="sm"
          onClick={() => handleToolbarClick(item)}
        >
          {item.props?.label || 'Action'}
        </Button>
      );
    }
    return null;
  };

  return (
    <div className={cn('space-y-4', className)}>
      {toolbar && toolbar.length > 0 && (
        <div className="flex justify-end gap-2">
          {toolbar.map(item => renderToolbarItem(item))}
        </div>
      )}
      <div className="rounded-md border">
        <div className="relative w-full overflow-auto">
          <table className="w-full caption-bottom text-sm">
            <thead className="[&_tr]:border-b">
              {table.getHeaderGroups().map((headerGroup) => (
                <tr key={headerGroup.id} className="border-b transition-colors hover:bg-muted/50">
                  {headerGroup.headers.map((header) => (
                    <th
                      key={header.id}
                      className="h-12 px-4 text-left align-middle font-medium text-muted-foreground [&:has([role=checkbox])]:pr-0"
                    >
                      {header.isPlaceholder
                        ? null
                        : flexRender(header.column.columnDef.header, header.getContext())}
                    </th>
                  ))}
                </tr>
              ))}
            </thead>
            <tbody className="[&_tr:last-child]:border-0">
              {table.getRowModel().rows.map((row) => (
                <tr
                  key={row.id}
                  className={cn(
                    'border-b transition-colors hover:bg-muted/50',
                    actions?.rowClick && 'cursor-pointer',
                    row.getIsSelected() && 'bg-muted'
                  )}
                  onClick={async (e) => {
                    const clickAction = rowAction || actions?.rowClick;
                    if (clickAction && !(e.target as HTMLElement).closest('button, input')) {
                      if (rowAction?.type === 'modal') {
                        setDetailLoading(true);
                        setDetailModalOpen(true);
                        try {
                          const url = rowAction.url.replace('{{row.id}}', row.original.id);
                          const response = await fetch(url);
                          const data = await response.json();
                          setDetailModalData(data);
                        } catch (err) {
                          console.error('Failed to load details:', err);
                        } finally {
                          setDetailLoading(false);
                        }
                      } else {
                        executeAction(clickAction, row.original);
                      }
                    }
                  }}
                >
                  {row.getVisibleCells().map((cell) => (
                    <td key={cell.id} className="p-4 align-middle [&:has([role=checkbox])]:pr-0">
                      {flexRender(cell.column.columnDef.cell, cell.getContext())}
                    </td>
                  ))}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      {pagination.enabled && (
        <div className="flex items-center justify-between px-2">
          <div className="flex-1 text-sm text-muted-foreground">
            {selection.enabled && table.getFilteredSelectedRowModel().rows.length > 0 && (
              <span>
                {table.getFilteredSelectedRowModel().rows.length} of{' '}
                {table.getFilteredRowModel().rows.length} row(s) selected.
              </span>
            )}
          </div>
          <div className="flex items-center space-x-6 lg:space-x-8">
            <div className="flex items-center space-x-2">
              <p className="text-sm font-medium">Rows per page</p>
              <Select
                value={`${table.getState().pagination.pageSize}`}
                onValueChange={(value) => {
                  table.setPageSize(Number(value));
                }}
              >
                <SelectTrigger className="h-8 w-[70px]">
                  <SelectValue placeholder={table.getState().pagination.pageSize} />
                </SelectTrigger>
                <SelectContent side="top">
                  {[10, 20, 30, 40, 50].map((pageSize) => (
                    <SelectItem key={pageSize} value={`${pageSize}`}>
                      {pageSize}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="flex w-[100px] items-center justify-center text-sm font-medium">
              Page {table.getState().pagination.pageIndex + 1} of {table.getPageCount()}
            </div>
            <div className="flex items-center space-x-2">
              <Button
                variant="outline"
                className="h-8 w-8 p-0"
                onClick={() => table.previousPage()}
                disabled={!table.getCanPreviousPage()}
              >
                <span className="sr-only">Go to previous page</span>
                <ChevronDown className="h-4 w-4 rotate-90" />
              </Button>
              <Button
                variant="outline"
                className="h-8 w-8 p-0"
                onClick={() => table.nextPage()}
                disabled={!table.getCanNextPage()}
              >
                <span className="sr-only">Go to next page</span>
                <ChevronDown className="h-4 w-4 -rotate-90" />
              </Button>
            </div>
          </div>
        </div>
      )}

      {/* Form Modal */}
      <Dialog open={formModalOpen} onOpenChange={setFormModalOpen}>
        <DialogContent className="max-w-2xl">
          <form onSubmit={handleFormSubmit}>
            <DialogHeader>
              <DialogTitle>{formModalConfig?.title || 'Form'}</DialogTitle>
            </DialogHeader>
            <div className="space-y-4 py-4">
            {formModalConfig?.fields?.map((field: any) => (
              <div key={field.name} className="space-y-2">
                <Label htmlFor={field.name}>
                  {field.label}
                  {field.required && <span className="text-destructive ml-1">*</span>}
                </Label>
                {field.type === 'textarea' ? (
                  <Textarea
                    id={field.name}
                    name={field.name}
                    placeholder={field.placeholder}
                    required={field.required}
                  />
                ) : field.type === 'select' ? (
                  <Select>
                    <SelectTrigger>
                      <SelectValue placeholder={field.placeholder} />
                    </SelectTrigger>
                    <SelectContent>
                      {field.options?.map((opt: any) => (
                        <SelectItem key={opt.value} value={opt.value}>
                          {opt.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                ) : (
                  <Input
                    id={field.name}
                    name={field.name}
                    type={field.type}
                    placeholder={field.placeholder}
                    required={field.required}
                    accept={field.accept}
                  />
                )}
              </div>
            ))}
          </div>
            <DialogFooter>
              <Button variant="outline" type="button" onClick={() => setFormModalOpen(false)}>
                Cancel
              </Button>
              <Button type="submit" disabled={formSubmitting}>
                {formSubmitting ? 'Submitting...' : (formModalConfig?.submitLabel || 'Submit')}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Detail Modal */}
      <Dialog open={detailModalOpen} onOpenChange={setDetailModalOpen}>
        <DialogContent className="max-w-2xl">
          <DialogHeader>
            <DialogTitle>{rowAction?.title || 'Details'}</DialogTitle>
          </DialogHeader>
          <div className="space-y-4 py-4">
            {detailLoading ? (
              <div className="flex items-center justify-center p-8">
                <div className="text-muted-foreground">Loading...</div>
              </div>
            ) : detailModalData ? (
              <div className="space-y-3">
                {rowAction?.fields?.map((field: any) => (
                  <div key={field.key} className="grid grid-cols-3 gap-4">
                    <div className="font-medium text-muted-foreground">{field.label}:</div>
                    <div className="col-span-2">
                      {field.type === 'date' 
                        ? new Date(detailModalData[field.key]).toLocaleString()
                        : detailModalData[field.key] || '—'
                      }
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <div className="text-center text-muted-foreground">No data available</div>
            )}
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setDetailModalOpen(false)}>
              Close
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
