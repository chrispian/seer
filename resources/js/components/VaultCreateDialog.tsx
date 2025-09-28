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
import { useCreateVault } from '../hooks/useVaults';

interface VaultCreateDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
}

export const VaultCreateDialog: React.FC<VaultCreateDialogProps> = ({
  open,
  onOpenChange,
}) => {
  const [name, setName] = useState('');
  const [description, setDescription] = useState('');
  const [errors, setErrors] = useState<Record<string, string>>({});

  const createVaultMutation = useCreateVault();

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});

    // Basic validation
    const newErrors: Record<string, string> = {};
    if (!name.trim()) {
      newErrors.name = 'Vault name is required';
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    try {
      await createVaultMutation.mutateAsync({
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
        setErrors({ submit: 'Failed to create vault' });
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
          <DialogTitle>Create New Vault</DialogTitle>
          <DialogDescription>
            Create a new vault to organize your projects and conversations.
          </DialogDescription>
        </DialogHeader>
        
        <form onSubmit={handleSubmit}>
          <div className="grid gap-4 py-4">
            <div className="grid gap-2">
              <Label htmlFor="name">Name</Label>
              <Input
                id="name"
                value={name}
                onChange={(e) => setName(e.target.value)}
                placeholder="Enter vault name"
                className={errors.name ? 'border-red-500' : ''}
                disabled={createVaultMutation.isPending}
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
                placeholder="Enter vault description"
                rows={3}
                disabled={createVaultMutation.isPending}
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
              disabled={createVaultMutation.isPending}
            >
              Cancel
            </Button>
            <Button
              type="submit"
              disabled={createVaultMutation.isPending || !name.trim()}
            >
              {createVaultMutation.isPending ? 'Creating...' : 'Create Vault'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
};