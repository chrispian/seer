import { useEffect, useState } from 'react'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
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
    const unsubscribe = slotBinder.subscribe(config.id, () => {
      setSearchTerm('')
      fetch({ search: '' })
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

  if (loading) {
    return <div className="p-4 text-center text-muted-foreground">Loading...</div>
  }

  if (error) {
    return <div className="p-4 text-center text-destructive">Error: {error.message}</div>
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

      <div className="border rounded-lg">
        <Table>
          <TableHeader>
            <TableRow>
              {config.columns?.map(column => (
                <TableHead key={column.key}>{column.label}</TableHead>
              ))}
            </TableRow>
          </TableHeader>
          <TableBody>
            {data.length === 0 ? (
              <TableRow>
                <TableCell colSpan={config.columns?.length || 1} className="text-center text-muted-foreground">
                  No data available
                </TableCell>
              </TableRow>
            ) : (
              data.map((row: any, index: number) => (
                <TableRow
                  key={row.id || index}
                  onClick={() => handleRowClick(row)}
                  className={config.rowAction ? 'cursor-pointer' : ''}
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
