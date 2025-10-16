import { componentRegistry } from '@/components/v2/ComponentRegistry'
import { RowsLayout } from '@/components/v2/layouts/RowsLayout'
import { ColumnsLayout } from '@/components/v2/layouts/ColumnsLayout'
import { DataTableComponent } from '@/components/v2/advanced/DataTableComponent'
import { SearchBarComponent } from '@/components/v2/composites/SearchBarComponent'
import { ButtonIconComponent } from '@/components/v2/primitives/ButtonIconComponent'

export function registerCoreComponents() {
  componentRegistry.register('rows', RowsLayout as any)
  componentRegistry.register('columns', ColumnsLayout as any)
  componentRegistry.register('table', DataTableComponent as any)
  componentRegistry.register('data-table', DataTableComponent as any)
  componentRegistry.register('search.bar', SearchBarComponent as any)
  componentRegistry.register('button.icon', ButtonIconComponent as any)
}
