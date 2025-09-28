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
import { Textarea } from './ui/textarea';
import { useCreateProject } from '../hooks/useProjects';
import { useAppStore } from '../stores/useAppStore';

interface ProjectCreateDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export const ProjectCreateDialog: React.FC<ProjectCreateDialogProps> = ({
  open,
  onOpenChange,
}) => {
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});

  const { currentVaultId, getCurrentVault } = useAppStore();
  const currentVault = getCurrentVault();
  const createProjectMutation = useCreateProject();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});

    // Basic validation
    const newErrors: Record<string, string> = {};
    if (!name.trim()) {
      newErrors.name = 'Project name is required';
    }
    if (!currentVaultId) {
      newErrors.submit = 'No vault selected. Please select a vault first.';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    try {
      await createProjectMutation.mutateAsync({
        vault_id: currentVaultId!,
        name: name.trim(),
        description: description.trim() || undefined,
      });

      // Reset form and close dialog
      setName('');
      setDescription('');
      setErrors({});
      onOpenChange(false);
    } catch (error) {
      if (error instanceof Error) {
        setErrors({ submit: error.message });
      } else {
        setErrors({ submit: 'Failed to create project' });
      }
    }
  };

  const handleCancel = () => {
    setName('');
    setDescription('');
    setErrors({});
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>Create New Project</DialogTitle>
          <DialogDescription>
            Create a new project in <strong>{currentVault?.name || 'the current vault'}</strong>.
          </DialogDescription>
        </DialogHeader>
        
        <form onSubmit={handleSubmit}>
          <div className="grid gap-4 py-4">
            {currentVault && (
              <div className="bg-gray-50 border border-gray-200 rounded-xs p-3">
                <p className="text-sm text-gray-600">
                  <strong>Target Vault:</strong> {currentVault.name}
                </p>
                {currentVault.description && (
                  <p className="text-xs text-gray-500 mt-1">
                    {currentVault.description}
                  </p>
                )}
              </div>
            )}
            
            <div className="grid gap-2">
              <Label htmlFor="name">Name</Label>
              <Input
                id="name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Enter project name"
                className={errors.name ? 'border-red-500' : ''}
                disabled={createProjectMutation.isPending}
              />
              {errors.name && (
                <p className="text-sm text-red-500">{errors.name}</p>
              )}
            </div>
            
            <div className="grid gap-2">
              <Label htmlFor="description">Description (Optional)</Label>
              <Textarea
                id="description"
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                placeholder="Enter project description"
                rows={3}
                disabled={createProjectMutation.isPending}
              />
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
              disabled={createProjectMutation.isPending}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={createProjectMutation.isPending || !name.trim() || !currentVaultId}
            >
              {createProjectMutation.isPending ? 'Creating...' : 'Create Project'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};