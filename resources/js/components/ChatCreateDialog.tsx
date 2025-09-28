import React, { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from './ui/dialog';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Label } from './ui/label';
import { useCreateChatSession } from '../hooks/useChatSessions';
import { useAppStore } from '../stores/useAppStore';

interface ChatCreateDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export const ChatCreateDialog: React.FC<ChatCreateDialogProps> = ({
  open,
  onOpenChange,
}) => {
  const [title, setTitle] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});

  const { currentVaultId, currentProjectId, getCurrentVault, getCurrentProject } = useAppStore();
  const currentVault = getCurrentVault();
  const currentProject = getCurrentProject();
  const createChatMutation = useCreateChatSession();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});

    // Basic validation
    const newErrors: Record<string, string> = {};
    if (!currentVaultId) {
      newErrors.submit = 'No vault selected. Please select a vault first.';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    try {
      await createChatMutation.mutateAsync({
        title: title.trim() || undefined, // Let backend generate default title if empty
      });

      // Reset form and close dialog
      setTitle('');
      setErrors({});
      onOpenChange(false);
    } catch (error) {
      if (error instanceof Error) {
        setErrors({ submit: error.message });
      } else {
        setErrors({ submit: 'Failed to create chat session' });
      }
    }
  };

  const handleCancel = () => {
    setTitle('');
    setErrors({});
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Create New Chat</DialogTitle>
          <DialogDescription>
            Start a new conversation in your current workspace.
          </DialogDescription>
        </DialogHeader>
        
        <form onSubmit={handleSubmit}>
          <div className="grid gap-4 py-4">
            {currentVault && (
              <div className="bg-gray-50 border border-gray-200 rounded-xs p-3">
                <p className="text-sm text-gray-600">
                  <strong>Vault:</strong> {currentVault.name}
                </p>
                {currentProject && (
                  <p className="text-sm text-gray-600 mt-1">
                    <strong>Project:</strong> {currentProject.name}
                  </p>
                )}
                {currentVault.description && (
                  <p className="text-xs text-gray-500 mt-1">
                    {currentVault.description}
                  </p>
                )}
              </div>
            )}
            
            <div className="grid gap-2">
              <Label htmlFor="title">Title (Optional)</Label>
              <Input
                id="title"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="Enter chat title (or leave blank for auto-generated)"
                disabled={createChatMutation.isPending}
              />
              <p className="text-xs text-gray-500">
                If left blank, a title will be generated from your first message.
              </p>
            </div>

            {errors.submit && (
              <div className="text-sm text-red-500 bg-red-50 border border-red-200 rounded-xs p-3">
                {errors.submit}
              </div>
            )}
          </div>
          
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={handleCancel}
              disabled={createChatMutation.isPending}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={createChatMutation.isPending || !currentVaultId}
            >
              {createChatMutation.isPending ? 'Creating...' : 'Start Chat'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};