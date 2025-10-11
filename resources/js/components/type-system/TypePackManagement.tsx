import React from 'react'
import { useState } from 'react'
import { TypePackList } from './TypePackList'
import { TypePackEditor } from './TypePackEditor'
import type { TypePack } from '@/lib/api/typePacks'

interface TypePackManagementProps {
  isOpen: boolean
  onClose: () => void
}

export function TypePackManagement({ isOpen, onClose }: TypePackManagementProps) {
  const [selectedTypePack, setSelectedTypePack] = useState<TypePack | null>(null)
  const [isEditorOpen, setIsEditorOpen] = useState(false)
  const [refreshKey, setRefreshKey] = useState(0)

  const handleSelectTypePack = (typePack: TypePack) => {
    setSelectedTypePack(typePack)
    setIsEditorOpen(true)
  }

  const handleCreateNew = () => {
    setSelectedTypePack(null)
    setIsEditorOpen(true)
  }

  const handleCloseEditor = () => {
    setIsEditorOpen(false)
    setSelectedTypePack(null)
  }

  const handleSave = (typePack: TypePack) => {
    setRefreshKey(prev => prev + 1)
    handleCloseEditor()
  }

  if (isEditorOpen) {
    return (
      <TypePackEditor
        isOpen={isEditorOpen}
        onClose={handleCloseEditor}
        typePack={selectedTypePack}
        onSave={handleSave}
      />
    )
  }

  return (
    <TypePackList
      key={refreshKey}
      isOpen={isOpen}
      onClose={onClose}
      onSelectTypePack={handleSelectTypePack}
      onCreateNew={handleCreateNew}
    />
  )
}
