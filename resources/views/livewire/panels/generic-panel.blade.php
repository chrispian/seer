<!-- Generic Command Panel -->
<div class="space-y-4">
    <div class="pixel-card pixel-card-electric p-4 glow-electric">
        <h3 class="text-lg font-semibold text-electric-blue mb-3">
            <x-heroicon-o-command-line class="inline w-5 h-5 mr-1"/>
            Command Result
        </h3>
        
        <div class="space-y-3">
            @if(isset($data['message']))
                <div class="bg-surface-elevated rounded-pixel p-3 pixel-card border-thin border-electric-blue/30">
                    <div class="text-sm text-gray-200">
                        {!! nl2br(e($data['message'])) !!}
                    </div>
                </div>
            @endif
            
            @if(isset($data['fragments']) && is_array($data['fragments']))
                @foreach($data['fragments'] as $fragment)
                    <div class="bg-surface-elevated rounded-pixel p-3 pixel-card border-thin border-electric-blue/30">
                        <div class="space-y-2">
                            @if(isset($fragment['type']))
                                <div class="flex items-center space-x-2">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-electric-blue/20 text-electric-blue border border-electric-blue/20">
                                        {{ $fragment['type'] }}
                                    </span>
                                    @if(isset($fragment['created_at']))
                                        <span class="text-xs text-gray-400">{{ \Carbon\Carbon::parse($fragment['created_at'])->diffForHumans() }}</span>
                                    @endif
                                </div>
                            @endif
                            <div class="text-sm text-gray-200">
                                {{ $fragment['message'] ?? $fragment['title'] ?? 'No content' }}
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
            
            @if(!isset($data['message']) && (!isset($data['fragments']) || empty($data['fragments'])))
                <div class="text-center py-8">
                    <x-heroicon-o-command-line class="w-12 h-12 text-gray-400 mx-auto mb-3"/>
                    <p class="text-gray-400">Command executed successfully</p>
                </div>
            @endif
        </div>
    </div>
</div>