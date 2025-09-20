<div class="space-y-6" wire:key="routing-rules-panel">
    <div class="flex items-center justify-between gap-4">
        <flux:heading size="lg">Vault Routing Rules</flux:heading>
        <div class="flex items-center gap-2">
            <flux:button variant="outline" size="sm" icon="arrow-path" wire:click="refreshRules" wire:loading.attr="disabled">
                Refresh
            </flux:button>
            <flux:button variant="primary" size="sm" icon="plus" wire:click="startCreate">
                New Rule
            </flux:button>
        </div>
    </div>

    @php
        $contextVault = collect($vaults)->firstWhere('id', $filters['scope_vault_id'] ?? null);
        $contextProject = collect($projects)->firstWhere('id', $filters['scope_project_id'] ?? null);
    @endphp

    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <flux:heading size="sm">Evaluation Context</flux:heading>
                <p class="text-sm text-zinc-500 dark:text-zinc-300">Rules evaluate in ascending priority. The first active match wins.</p>
            </div>

            <div class="flex items-center gap-2">
                <flux:badge size="sm" variant="subtle">
                    <flux:icon icon="cube-transparent" variant="micro" />
                    {{ $contextVault['name'] ?? 'All vaults' }}
                </flux:badge>
                <flux:badge size="sm" variant="subtle">
                    <flux:icon icon="briefcase" variant="micro" />
                    {{ $contextProject['name'] ?? 'All projects' }}
                </flux:badge>
            </div>
        </div>

        @if ($flash)
            @php
                $flashTone = match ($flash['variant']) {
                    'danger' => 'bg-rose-500/10 dark:bg-rose-500/20 border-rose-500/30 dark:border-rose-500/40',
                    'primary' => 'bg-sky-500/10 dark:bg-sky-500/20 border-sky-500/30 dark:border-sky-500/40',
                    default => 'bg-emerald-500/10 dark:bg-emerald-500/20 border-emerald-500/30 dark:border-emerald-500/40',
                };
            @endphp
            <div class="rounded-xl border px-4 py-3 flex items-start justify-between gap-4 {{ $flashTone }}">
                <div>
                    <div class="text-sm font-semibold text-zinc-800 dark:text-white">{{ $flash['title'] }}</div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-300">{{ $flash['body'] }}</div>
                </div>
                <flux:button variant="ghost" size="xs" icon="x-mark" wire:click="clearFlash" />
            </div>
        @endif

        @if (empty($rules))
            <div class="py-12 text-center text-sm text-zinc-500 dark:text-zinc-300">
                <flux:icon icon="adjustments-horizontal" class="mx-auto mb-3 text-zinc-400 dark:text-zinc-500" variant="outline" />
                <div class="font-medium text-zinc-600 dark:text-zinc-200">No routing rules yet</div>
                <p class="mt-2">Create a rule to automatically direct new fragments to the right vault or project.</p>
            </div>
        @else
            <div class="overflow-hidden rounded-xl border border-zinc-200/60 dark:border-white/5">
                <flux:table class="min-w-full">
                    <flux:table.columns>
                        <flux:table.column>Name</flux:table.column>
                        <flux:table.column>Match</flux:table.column>
                        <flux:table.column>Destination</flux:table.column>
                        <flux:table.column align="center">Priority</flux:table.column>
                        <flux:table.column align="center">Status</flux:table.column>
                        <flux:table.column align="end">Actions</flux:table.column>
                    </flux:table.columns>
                    <flux:table.rows>
                        @foreach ($rules as $rule)
                            <flux:table.row wire:key="routing-rule-{{ $rule['id'] }}">
                                <flux:table.cell>
                                    <div class="text-sm font-medium text-zinc-800 dark:text-white">{{ $rule['name'] }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                        @php
                                            $note = $rule['notes'] ?? null;
                                            $contextSummary = [];
                                            if ($rule['scope_vault_id']) {
                                                $vault = collect($vaults)->firstWhere('id', $rule['scope_vault_id']);
                                                $contextSummary[] = 'Vault: ' . ($vault['name'] ?? '#'.$rule['scope_vault_id']);
                                            }
                                            if ($rule['scope_project_id']) {
                                                $project = collect($projects)->firstWhere('id', $rule['scope_project_id']);
                                                $contextSummary[] = 'Project: ' . ($project['name'] ?? '#'.$rule['scope_project_id']);
                                            }
                                        @endphp
                                        {{ $note ? \Illuminate\Support\Str::limit($note, 80) : ($contextSummary ? implode(' · ', $contextSummary) : 'Global rule') }}
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-col gap-1 text-sm text-zinc-700 dark:text-zinc-200">
                                        <div class="flex items-center gap-2">
                                            <flux:badge size="sm" variant="subtle">{{ strtoupper($rule['match_type']) }}</flux:badge>
                                            <span>{{ $rule['match_value'] ?? '—' }}</span>
                                        </div>
                                        @if (!empty($rule['conditions']))
                                            <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ json_encode($rule['conditions'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}
                                            </div>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @php
                                        $targetVault = collect($vaults)->firstWhere('id', $rule['target_vault_id']);
                                        $targetProject = collect($projects)->firstWhere('id', $rule['target_project_id']);
                                    @endphp
                                    <div class="text-sm text-zinc-700 dark:text-zinc-200">
                                        {{ $targetVault['name'] ?? 'Unknown vault' }}
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $targetProject['name'] ?? 'No project override' }}
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-200">{{ $rule['priority'] }}</span>
                                </flux:table.cell>
                                <flux:table.cell align="center">
                                    <flux:badge size="sm" variant="{{ $rule['is_active'] ? 'primary' : 'ghost' }}">
                                        {{ $rule['is_active'] ? 'Active' : 'Paused' }}
                                    </flux:badge>
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button size="xs" variant="ghost" icon="pencil-square" wire:click="startEdit({{ $rule['id'] }})" />
                                        <flux:button size="xs" variant="ghost" icon="trash" wire:click="confirmDelete({{ $rule['id'] }})" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>
        @endif
    </flux:card>

    <flux:modal wire:model="showEditor" name="routing-editor" variant="flyout" position="right" :dismissible="false">
        <form class="space-y-5 max-w-xl" wire:submit.prevent="saveRule">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="md">{{ $editingId ? 'Edit routing rule' : 'New routing rule' }}</flux:heading>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Define how incoming fragments route into vaults and projects.</p>
                </div>
                <flux:button variant="ghost" size="sm" icon="x-mark" wire:click="cancelEditor" type="button" aria-label="Close" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2 space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Rule Name</label>
                    <flux:input wire:model.defer="name" placeholder="e.g. Client mentions to CRM" />
                    @error('name')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Match Type</label>
                    <select wire:model="matchType" class="w-full h-10 rounded-lg border border-zinc-200 dark:border-white/10 bg-transparent px-3 text-sm text-zinc-700 dark:text-zinc-200">
                        <option value="keyword">Keyword contains</option>
                        <option value="tag">Tag match</option>
                        <option value="type">Fragment type</option>
                        <option value="regex">Regex</option>
                    </select>
                    @error('matchType')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Match Value</label>
                    <flux:input wire:model.defer="matchValue" placeholder="e.g. finance" />
                    @error('matchValue')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2 space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">JSON Conditions (optional)</label>
                    <textarea wire:model.defer="conditionsInput" rows="3" class="w-full rounded-lg border border-zinc-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-700 dark:text-zinc-200" placeholder='{"keywords": ["client"], "threshold": 0.65}'></textarea>
                    @error('conditionsInput')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Target Vault</label>
                    <select wire:model="targetVaultId" class="w-full h-10 rounded-lg border border-zinc-200 dark:border-white/10 bg-transparent px-3 text-sm text-zinc-700 dark:text-zinc-200">
                        <option value="">Select a vault</option>
                        @foreach ($vaults as $vault)
                            <option value="{{ $vault['id'] }}">{{ $vault['name'] }}</option>
                        @endforeach
                    </select>
                    @error('targetVaultId')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Target Project (optional)</label>
                    <select wire:model="targetProjectId" class="w-full h-10 rounded-lg border border-zinc-200 dark:border-white/10 bg-transparent px-3 text-sm text-zinc-700 dark:text-zinc-200">
                        <option value="">No override</option>
                        @foreach ($this->projectOptions as $project)
                            <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                        @endforeach
                    </select>
                    @error('targetProjectId')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Context Vault</label>
                    <select wire:model="scopeVaultId" class="w-full h-10 rounded-lg border border-zinc-200 dark:border-white/10 bg-transparent px-3 text-sm text-zinc-700 dark:text-zinc-200">
                        <option value="">All vaults</option>
                        @foreach ($vaults as $vault)
                            <option value="{{ $vault['id'] }}">{{ $vault['name'] }}</option>
                        @endforeach
                    </select>
                    @error('scopeVaultId')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Context Project</label>
                    <select wire:model="scopeProjectId" class="w-full h-10 rounded-lg border border-zinc-200 dark:border-white/10 bg-transparent px-3 text-sm text-zinc-700 dark:text-zinc-200">
                        <option value="">All projects</option>
                        @foreach ($this->scopeProjectOptions as $project)
                            <option value="{{ $project['id'] }}">{{ $project['name'] }}</option>
                        @endforeach
                    </select>
                    @error('scopeProjectId')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Priority</label>
                    <flux:input type="number" min="1" max="1000" wire:model.defer="priority" />
                    @error('priority')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Status</label>
                    <div class="flex items-center gap-2">
                        <input id="rule-active" type="checkbox" wire:model="isActive" class="h-4 w-4 rounded border-zinc-300 text-sky-500 focus:ring-sky-500" />
                        <label for="rule-active" class="text-sm text-zinc-600 dark:text-zinc-300">Rule is active</label>
                    </div>
                    @error('isActive')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="sm:col-span-2 space-y-2">
                    <label class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Notes</label>
                    <textarea wire:model.defer="notes" rows="2" class="w-full rounded-lg border border-zinc-200 dark:border-white/10 bg-transparent px-3 py-2 text-sm text-zinc-700 dark:text-zinc-200" placeholder="Internal note about why this rule exists"></textarea>
                    @error('notes')
                        <p class="text-xs text-rose-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <flux:button type="button" variant="ghost" wire:click="cancelEditor">Cancel</flux:button>
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                    {{ $editingId ? 'Save changes' : 'Create rule' }}
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showDeleteConfirm" name="routing-delete" variant="flyout" position="bottom">
        <div class="space-y-4 max-w-2xl">
            <flux:heading size="md">Delete routing rule?</flux:heading>
            <p class="text-sm text-zinc-500 dark:text-zinc-300">This action cannot be undone. Fragments will ignore this rule once removed.</p>
            <div class="flex items-center justify-end gap-2">
                <flux:button variant="ghost" wire:click="cancelDelete">Cancel</flux:button>
                <flux:button variant="danger" icon="trash" wire:click="deleteRule" wire:loading.attr="disabled">
                    Delete rule
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
