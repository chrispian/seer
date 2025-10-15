import { useEffect, useState } from 'react'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Skeleton } from '@/components/ui/skeleton'
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
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
  const [detailModalOpen, setDetailModalOpen] = useState(false)
  const [selectedRow, setSelectedRow] = useState<any>(null)
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
      if (config.rowAction.type === 'modal') {
        setSelectedRow(row)
        setDetailModalOpen(true)
      } else {
        await execute(config.rowAction, { row })
      }
    }
  }

  const ToolbarComponent = componentRegistry.get('button.icon')
  const DetailModalComponent = componentRegistry.get('detail')

  return (
    <>
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
                    <TableCell key={column.key}>
                      {column.key === 'avatar_url' ? (
                        <Avatar className="h-8 w-8">
                          <AvatarImage src={row[column.key]} alt={row.name} />
                          <AvatarFallback>{row.name?.substring(0, 2).toUpperCase()}</AvatarFallback>
                        </Avatar>
                      ) : (
                        row[column.key]
                      )}
                    </TableCell>
                  ))}
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>
      </div>

      {/* Detail Modal */}
      {config.rowAction?.type === 'modal' && DetailModalComponent && (
        <Dialog open={detailModalOpen} onOpenChange={setDetailModalOpen}>
          <DialogContent className="sm:max-w-2xl max-h-[80vh] overflow-y-auto">
            <DialogHeader>
              <DialogTitle>{config.rowAction.title || 'Details'}</DialogTitle>
            </DialogHeader>
            <DetailModalComponent
              config={{
                id: 'detail-modal',
                type: 'detail',
                url: config.rowAction.url?.replace('{{row.id}}', selectedRow?.id),
                fields: config.rowAction.fields,
              }}
              data={selectedRow}
            />
          </DialogContent>
        </Dialog>
      )}
    </>
  )
}
