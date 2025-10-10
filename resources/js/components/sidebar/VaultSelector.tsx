import React from 'react'
import { Archive, ChevronDown, Plus } from 'lucide-react'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu'

interface Vault {
  id: string
  name: string
}

interface VaultSelectorProps {
  currentVault: Vault | null
  vaults: Vault[]
  isLoading?: boolean
  onVaultChange: (vaultId: string) => void
  onCreateVault: () => void
}

export function VaultSelector({
  currentVault,
  vaults,
  isLoading = false,
  onVaultChange,
  onCreateVault
}: VaultSelectorProps) {
  return (
    <div className="p-2 md:p-3 border-b">
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            variant="ghost"
            className="w-full justify-start h-12 px-3 data-[state=open]:bg-gray-100"
            disabled={isLoading}
          >
            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-black text-white mr-3">
              <Archive className="size-4" />
            </div>
            <div className="grid flex-1 text-left text-sm leading-tight">
              <span className="truncate font-semibold">
                {currentVault?.name || 'Loading...'}
              </span>
              <span className="truncate text-xs text-gray-500">Vault</span>
            </div>
            <ChevronDown className="ml-auto" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent
          className="w-[--radix-dropdown-menu-trigger-width] min-w-56 rounded-lg"
          side="bottom"
          align="start"
          sideOffset={4}
        >
          {vaults.map((vault) => (
            <DropdownMenuItem
              key={vault.id}
              onClick={() => onVaultChange(vault.id)}
              className="gap-2 p-2"
            >
              <div className="flex size-6 items-center justify-center rounded-sm border">
                <Archive className="size-4 shrink-0" />
              </div>
              {vault.name}
            </DropdownMenuItem>
          ))}
          <DropdownMenuSeparator />
          <DropdownMenuItem onClick={onCreateVault} className="gap-2 p-2">
            <div className="flex size-6 items-center justify-center rounded-md border border-dashed">
              <Plus className="size-4" />
            </div>
            <div className="font-medium text-gray-500">Add vault</div>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  )
}
