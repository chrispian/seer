import { componentRegistry } from './ComponentRegistry'
import { TableComponent } from './primitives/TableComponent'
import { SearchBarComponent } from './primitives/SearchBarComponent'
import { ButtonIconComponent } from './primitives/ButtonIconComponent'
import { DetailComponent } from './primitives/DetailComponent'

export function registerComponents() {
  componentRegistry.register('table', TableComponent)
  componentRegistry.register('search.bar', SearchBarComponent)
  componentRegistry.register('button.icon', ButtonIconComponent)
  componentRegistry.register('detail', DetailComponent)
}
