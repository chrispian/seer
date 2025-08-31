<!-- Recall Command Panel -->
<div class="space-y-4">
    @if(isset($data['fragments']) && count($data['fragments']) > 0)
        <div class="pixel-card pixel-card-cyan p-4 glow-cyan">
            <h3 class="text-lg font-semibold text-neon-cyan mb-3">
                <x-heroicon-o-clipboard-document-list class="inline w-5 h-5 mr-1"/>
                {{ ucfirst($data['type'] ?? 'Items') }} ({{ count($data['fragments']) }})
            </h3>
            
            <div class="space-y-3">
                @foreach($data['fragments'] as $fragment)
                    <div class="bg-surface-elevated rounded-pixel p-3 pixel-card border-thin border-neon-cyan/30">
                        @if($fragment['type'] === 'todo')
                            <!-- Todo Item Display -->
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0 mt-1">
                                    <div class="w-4 h-4 border-2 border-neon-cyan/60 rounded-sm"></div>
                                </div>
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-gray-200">{{ $fragment['title'] ?? $fragment['message'] }}</div>
                                    @if(isset($fragment['message']) && $fragment['message'] !== ($fragment['title'] ?? ''))
                                        <div class="text-xs text-gray-400 mt-1">{{ $fragment['message'] }}</div>
                                    @endif
                                    <div class="flex items-center space-x-2 mt-2">
                                        @if(isset($fragment['tags']) && is_array($fragment['tags']))
                                            @foreach(array_slice($fragment['tags'], 0, 3) as $tag)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-neon-cyan/20 text-neon-cyan">#{{ $tag }}</span>
                                            @endforeach
                                        @endif
                                        <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($fragment['created_at'])->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </div>
                        @else
                            <!-- Generic Fragment Display -->
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-900/20 text-blue-400 border border-blue-500/20">
                                        {{ $fragment['type'] }}
                                    </span>
                                    <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($fragment['created_at'])->diffForHumans() }}</span>
                                </div>
                                <div class="text-sm font-medium text-gray-200">{{ $fragment['title'] ?? $fragment['message'] }}</div>
                                @if(isset($fragment['message']) && $fragment['message'] !== ($fragment['title'] ?? ''))
                                    <div class="text-xs text-gray-400">{{ $fragment['message'] }}</div>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <x-heroicon-o-magnifying-glass class="w-12 h-12 text-gray-400 mx-auto mb-3"/>
            <p class="text-gray-400">
                @if(isset($data['type']))
                    No {{ $data['type'] }} fragments found
                @else
                    No fragments found
                @endif
            </p>
        </div>
    @endif
</div>