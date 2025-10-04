import React, { useState } from 'react'
import { Card, CardContent, CardHeader } from '@/components/ui/card'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { 
  Check, 
  X, 
  Archive, 
  MoreHorizontal, 
  Calendar,
  Tag,
  Folder,
  FileText,
  Edit3,
  Loader2
} from 'lucide-react'
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator
} from '@/components/ui/dropdown-menu'
import { InboxFragment, AcceptFragmentData } from '../hooks/useInbox'
import { TypeBadge } from '../../type-system/components/TypeBadge'

interface FragmentReviewCardProps {
  fragment: InboxFragment
  isSelected: boolean
  onToggleSelection: (fragmentId: number) => void
  onAccept: (fragmentId: number, data: AcceptFragmentData) => Promise<void>
  onArchive: (fragmentId: number, reason?: string) => Promise<void>
  onSkip: (fragmentId: number, reason?: string) => Promise<void>
  onReopen?: (fragmentId: number) => Promise<void>
  isAccepting?: boolean
  isArchiving?: boolean
  isSkipping?: boolean
  isReopening?: boolean
}

export function FragmentReviewCard({
  fragment,
  isSelected,
  onToggleSelection,
  onAccept,
  onArchive,
  onSkip,
  onReopen,
  isAccepting = false,
  isArchiving = false,
  isSkipping = false,
  isReopening = false
}: FragmentReviewCardProps) {
  const [isEditing, setIsEditing] = useState(false)
  const [editData, setEditData] = useState({
    title: fragment.title,
    type: fragment.type,
    category: fragment.category || '',
    vault: fragment.vault || '',
    tags: fragment.tags.join(', ')
  })
  const [actionReason, setActionReason] = useState('')
  const [showReasonInput, setShowReasonInput] = useState<'archive' | 'skip' | null>(null)

  const formatTimeAgo = (dateString: string) => {
    const date = new Date(dateString)
    const now = new Date()
    const diffInHours = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60))
    
    if (diffInHours < 1) return 'Just now'
    if (diffInHours < 24) return `${diffInHours}h ago`
    const diffInDays = Math.floor(diffInHours / 24)
    if (diffInDays < 7) return `${diffInDays}d ago`
    return date.toLocaleDateString()
  }

  const handleAccept = async () => {
    const acceptData: AcceptFragmentData = {}
    
    if (isEditing) {
      acceptData.edits = {
        title: editData.title !== fragment.title ? editData.title : undefined,
        type: editData.type !== fragment.type ? editData.type : undefined,
        category: editData.category !== (fragment.category || '') ? editData.category : undefined,
        vault: editData.vault !== (fragment.vault || '') ? editData.vault : undefined,
        tags: editData.tags !== fragment.tags.join(', ') ? editData.tags.split(',').map(t => t.trim()).filter(t => t) : undefined
      }
    }

    await onAccept(fragment.id, acceptData)
    setIsEditing(false)
  }

  const handleArchive = async () => {
    await onArchive(fragment.id, actionReason || undefined)
    setShowReasonInput(null)
    setActionReason('')
  }

  const handleSkip = async () => {
    await onSkip(fragment.id, actionReason || undefined)
    setShowReasonInput(null)
    setActionReason('')
  }

  const handleReopen = async () => {
    if (onReopen) {
      await onReopen(fragment.id)
    }
  }

  const isPending = fragment.inbox_status === 'pending'
  const canReopen = fragment.inbox_status !== 'pending' && onReopen

  return (
    <Card className={`border transition-all duration-200 ${
      isSelected ? 'ring-2 ring-primary border-primary' : 'border-border'
    } ${!isPending ? 'opacity-75' : ''}`}>
      <CardHeader className="pb-3">
        <div className="flex items-start justify-between">
          <div className="flex items-start space-x-3 flex-1 min-w-0">
            {isPending && (
              <Checkbox
                checked={isSelected}
                onCheckedChange={() => onToggleSelection(fragment.id)}
                className="mt-1 flex-shrink-0"
              />
            )}
            <div className="flex-1 min-w-0">
              {isEditing ? (
                <Input
                  value={editData.title}
                  onChange={(e) => setEditData(prev => ({ ...prev, title: e.target.value }))}
                  className="font-medium mb-2"
                  placeholder="Fragment title"
                />
              ) : (
                <h3 className="font-medium text-sm leading-tight break-words">
                  {fragment.title}
                </h3>
              )}
              
              <div className="flex items-center gap-2 mt-2 text-xs text-muted-foreground">
                <div className="flex items-center gap-1">
                  <FileText className="w-3 h-3" />
                  {isEditing ? (
                    <Input
                      value={editData.type}
                      onChange={(e) => setEditData(prev => ({ ...prev, type: e.target.value }))}
                      className="text-xs h-6 w-20"
                      placeholder="Type"
                    />
                  ) : (
                    <TypeBadge 
                      type={fragment.type} 
                      size="sm" 
                      variant="outline"
                      showIcon={true}
                    />
                  )}
                </div>
                
                <div className="flex items-center gap-1">
                  <Calendar className="w-3 h-3" />
                  {formatTimeAgo(fragment.inbox_at)}
                </div>

                <Badge variant={
                  fragment.inbox_status === 'pending' ? 'default' :
                  fragment.inbox_status === 'accepted' ? 'secondary' :
                  fragment.inbox_status === 'archived' ? 'outline' :
                  'destructive'
                } className="text-xs">
                  {fragment.inbox_status}
                </Badge>
              </div>

              {/* Tags and metadata */}
              <div className="mt-2 space-y-1">
                {isEditing ? (
                  <div className="space-y-1">
                    <Input
                      value={editData.tags}
                      onChange={(e) => setEditData(prev => ({ ...prev, tags: e.target.value }))}
                      className="text-xs h-6"
                      placeholder="Tags (comma-separated)"
                    />
                    <div className="flex gap-2">
                      <Input
                        value={editData.category}
                        onChange={(e) => setEditData(prev => ({ ...prev, category: e.target.value }))}
                        className="text-xs h-6"
                        placeholder="Category"
                      />
                      <Input
                        value={editData.vault}
                        onChange={(e) => setEditData(prev => ({ ...prev, vault: e.target.value }))}
                        className="text-xs h-6"
                        placeholder="Vault"
                      />
                    </div>
                  </div>
                ) : (
                  <div className="flex flex-wrap gap-1">
                    {fragment.tags.map((tag, index) => (
                      <Badge key={index} variant="secondary" className="text-xs">
                        <Tag className="w-3 h-3 mr-1" />
                        {tag}
                      </Badge>
                    ))}
                    {fragment.category && (
                      <Badge variant="outline" className="text-xs">
                        <Folder className="w-3 h-3 mr-1" />
                        {fragment.category}
                      </Badge>
                    )}
                  </div>
                )}
              </div>
            </div>
          </div>
          
          {/* Actions */}
          <div className="flex items-center gap-1 flex-shrink-0 ml-2">
            {isPending && (
              <>
                <Button
                  size="sm"
                  variant="ghost"
                  onClick={() => setIsEditing(!isEditing)}
                  className="h-7 w-7 p-0"
                >
                  <Edit3 className="w-3 h-3" />
                </Button>
                
                <Button
                  size="sm"
                  onClick={handleAccept}
                  disabled={isAccepting}
                  className="h-7 px-2"
                >
                  {isAccepting ? (
                    <Loader2 className="w-3 h-3 animate-spin" />
                  ) : (
                    <Check className="w-3 h-3" />
                  )}
                </Button>
              </>
            )}
            
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button
                  size="sm"
                  variant="ghost"
                  className="h-7 w-7 p-0"
                  disabled={isArchiving || isSkipping || isReopening}
                >
                  {(isArchiving || isSkipping || isReopening) ? (
                    <Loader2 className="w-3 h-3 animate-spin" />
                  ) : (
                    <MoreHorizontal className="w-3 h-3" />
                  )}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                {isPending && (
                  <>
                    <DropdownMenuItem onClick={() => setShowReasonInput('archive')}>
                      <Archive className="w-4 h-4 mr-2" />
                      Archive
                    </DropdownMenuItem>
                    <DropdownMenuItem onClick={() => setShowReasonInput('skip')}>
                      <X className="w-4 h-4 mr-2" />
                      Skip
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                  </>
                )}
                {canReopen && (
                  <DropdownMenuItem onClick={handleReopen}>
                    <FileText className="w-4 h-4 mr-2" />
                    Reopen
                  </DropdownMenuItem>
                )}
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </CardHeader>
      
      {/* Content preview */}
      {fragment.content && (
        <CardContent className="pt-0">
          <div className="text-xs text-muted-foreground bg-muted/50 p-2 rounded border-l-2 border-border">
            {fragment.content.length > 200 
              ? `${fragment.content.substring(0, 200)}...` 
              : fragment.content
            }
          </div>
        </CardContent>
      )}

      {/* Reason input */}
      {showReasonInput && (
        <CardContent className="pt-0">
          <div className="space-y-2">
            <Textarea
              value={actionReason}
              onChange={(e) => setActionReason(e.target.value)}
              placeholder={`Reason for ${showReasonInput}ing this fragment (optional)`}
              className="text-xs"
              rows={2}
            />
            <div className="flex gap-2 justify-end">
              <Button
                size="sm"
                variant="outline"
                onClick={() => {
                  setShowReasonInput(null)
                  setActionReason('')
                }}
              >
                Cancel
              </Button>
              <Button
                size="sm"
                onClick={showReasonInput === 'archive' ? handleArchive : handleSkip}
                variant={showReasonInput === 'archive' ? 'default' : 'destructive'}
              >
                {showReasonInput === 'archive' ? 'Archive' : 'Skip'}
              </Button>
            </div>
          </div>
        </CardContent>
      )}

      {/* Fragment reason display */}
      {fragment.inbox_reason && (
        <CardContent className="pt-0">
          <div className="text-xs text-muted-foreground bg-yellow-50 p-2 rounded border-l-2 border-yellow-200">
            <strong>Reason:</strong> {fragment.inbox_reason}
          </div>
        </CardContent>
      )}
    </Card>
  )
}