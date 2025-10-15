import { useEffect, useState } from 'react'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import type { ComponentConfig } from '../types'
import { useDataSource } from '../hooks/useDataSource'
import { useAction } from '../hooks/useAction'
import { slotBinder } from '../SlotBinder'
import { componentRegistry } from '../ComponentRegistry'

interface TableComponentProps {
  config: ComponentConfig
}

export function TableComponent({ config }: TableComponentProps) {
  const [searchTerm, setSearchTerm] = useState('')
  const { data, loading, error, fetch } = useDataSource({
    dataSource: config.dataSource || '',
    search: searchTerm,
    autoFetch: true,
  })
  const { execute } = useAction()

  useEffect(() => {
    const unsubscribe = slotBinder.subscribe(config.id, (update: any) => {
      if (update.search !== undefined) {
        setSearchTerm(update.search)
        fetch({ search: update.search })
      } else if (update.refresh) {
        fetch({})
      }
    })

    return () => {
      unsubscribe()
    }
  }, [config.id, fetch])

  const handleRowClick = async (row: any) => {
    if (config.rowAction) {
      await execute(config.rowAction, { row })
    }
  }

  const ToolbarComponent = componentRegistry.get('button.icon')

  return (
    <div className="space-y-4">
      {config.toolbar && config.toolbar.length > 0 && (
        <div className="flex justify-end gap-2">
          {config.toolbar.map(toolbarItem => {
            if (ToolbarComponent) {
              return <ToolbarComponent key={toolbarItem.id} config={toolbarItem} />
            }
            return null
          })}
        </div>
      )}

      <div className="border rounded-lg min-h-[24rem]">
        <Table>
          <TableHeader>
            <TableRow>
              {config.columns?.map(column => (
                <TableHead key={column.key}>{column.label}</TableHead>
              ))}
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              Array.from({ length: 10 }).map((_, index) => (
                <TableRow key={`skeleton-${index}`}>
                  {config.columns?.map((column) => (
                    <TableCell key={column.key}>
                      <Skeleton className="h-4 w-full" />
                    </TableCell>
                  ))}
                </TableRow>
              ))
            ) : error ? (
              <TableRow>
                <TableCell colSpan={config.columns?.length || 1} className="text-center text-destructive h-64">
                  Error: {error.message}
                </TableCell>
              </TableRow>
            ) : data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={config.columns?.length || 1} className="text-center text-muted-foreground h-64">
                  No data available
                </TableCell>
              </TableRow>
            ) : (
              data.map((row: any, index: number) => (
                <TableRow
                  key={row.id || index}
                  onClick={() => handleRowClick(row)}
                  className={config.rowAction ? 'cursor-pointer hover:bg-muted/50' : ''}
                >
                  {config.columns?.map(column => (
                    <TableCell key={column.key}>{row[column.key]}</TableCell>
                  ))}
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
    </div>
  )
}
