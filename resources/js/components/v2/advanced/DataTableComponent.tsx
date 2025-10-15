import React from 'react';
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
    data: any[];
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
    data = [],
    pagination = { enabled: false, pageSize: 10 },
    selection = { enabled: false, type: 'multiple' },
    actions,
    loading = false,
    emptyText = 'No data available',
    className,
  } = props;

  const [sorting, setSorting] = React.useState<SortingState>([]);
  const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([]);
  const [rowSelection, setRowSelection] = React.useState<RowSelectionState>({});

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

  if (!data.length) {
    return (
      <div className="w-full h-64 flex items-center justify-center border rounded-lg">
        <div className="text-center">
          <p className="text-muted-foreground">{emptyText}</p>
        </div>
      </div>
    );
  }

  return (
    <div className={cn('space-y-4', className)}>
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
                  onClick={(e) => {
                    if (actions?.rowClick && !(e.target as HTMLElement).closest('button, input')) {
                      executeAction(actions.rowClick, row.original);
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
    </div>
  );
}
