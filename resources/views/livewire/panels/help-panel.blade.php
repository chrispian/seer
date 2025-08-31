<!-- Help Command Panel -->
<div class="space-y-4">
    <div class="pixel-card pixel-card-electric p-4 glow-electric">
        <h3 class="text-lg font-semibold text-electric-blue mb-3">
            <x-heroicon-o-question-mark-circle class="inline w-5 h-5 mr-1"/>
            Available Commands
        </h3>
        
        <div class="space-y-3">
            @foreach($data['commands'] ?? [] as $command)
                <div class="bg-surface-elevated rounded-pixel p-3 pixel-card border-thin border-electric-blue/30">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <code class="bg-electric-blue/20 text-electric-blue px-2 py-1 rounded text-sm font-mono">{{ $command['name'] }}</code>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm text-gray-200">{{ $command['description'] }}</div>
                            @if(isset($command['examples']) && is_array($command['examples']))
                                <div class="mt-2">
                                    <div class="text-xs font-medium text-gray-400 mb-1">Examples:</div>
                                    @foreach($command['examples'] as $example)
                                        <code class="block bg-gray-800 text-gray-300 px-2 py-1 rounded text-xs font-mono mb-1">{{ $example }}</code>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    
    @if(isset($data['message']))
        <div class="text-sm text-gray-300 bg-surface-elevated rounded-pixel p-3">
            {!! nl2br(e($data['message'])) !!}
        </div>
    @endif
</div>