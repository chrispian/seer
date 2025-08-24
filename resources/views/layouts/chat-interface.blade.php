<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seer - Chat Interface</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography,aspect-ratio,container-queries"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'mono': ['JetBrains Mono', 'Monaco', 'Consolas', 'monospace'],
                    },
                    colors: {
                        'hot-pink': '#FF1493',
                        'electric-blue': '#00BFFF', 
                        'neon-cyan': '#00FFFF',
                        'deep-purple': '#4B0082',
                        'bright-pink': '#FF69B4',
                        'surface': '#0f1419',
                        'surface-2': '#1a1f2e',
                        'surface-card': '#242b3d',
                        'surface-elevated': '#2d3548',
                        'text-primary': '#f8fafc',
                        'text-secondary': '#cbd5e1',
                        'text-muted': '#94a3b8',
                    },
                    borderRadius: {
                        'pixel': '3px',
                    },
                    borderWidth: {
                        'thin': '0.5px',
                    }
                }
            }
        }
    </script>
    <style>
        /* Pixelated corner effects inspired by pixel-style.jpg */
        .pixel-corner {
            position: relative;
        }
        
        .pixel-corner::before {
            content: '';
            position: absolute;
            top: -1px;
            left: -1px;
            right: -1px;
            bottom: -1px;
            background: 
                radial-gradient(circle at 2px 2px, currentColor 1px, transparent 1px),
                radial-gradient(circle at 2px calc(100% - 2px), currentColor 1px, transparent 1px),
                radial-gradient(circle at calc(100% - 2px) 2px, currentColor 1px, transparent 1px),
                radial-gradient(circle at calc(100% - 2px) calc(100% - 2px), currentColor 1px, transparent 1px);
            background-size: 4px 4px;
            background-repeat: no-repeat;
            pointer-events: none;
            z-index: -1;
        }
        
        .pixel-card {
            position: relative;
            border: 0.5px solid rgba(255, 255, 255, 0.1);
            background: rgba(36, 43, 61, 0.8);
            border-radius: 3px;
        }
        
        .pixel-card-pink {
            border-color: #FF1493;
            background: linear-gradient(135deg, rgba(255, 20, 147, 0.1) 0%, rgba(255, 20, 147, 0.05) 100%);
        }
        
        .pixel-card-blue {
            border-color: #00BFFF;
            background: linear-gradient(135deg, rgba(0, 191, 255, 0.1) 0%, rgba(0, 191, 255, 0.05) 100%);
        }
        
        .pixel-card-cyan {
            border-color: #00FFFF;
            background: linear-gradient(135deg, rgba(0, 255, 255, 0.1) 0%, rgba(0, 255, 255, 0.05) 100%);
        }
        
        /* Thin border utility */
        .border-thin {
            border-width: 0.5px;
        }
        
        /* Hover glow effects */
        .glow-pink:hover {
            box-shadow: 0 0 20px rgba(255, 20, 147, 0.4);
        }
        
        .glow-blue:hover {
            box-shadow: 0 0 20px rgba(0, 191, 255, 0.4);
        }
        
        .glow-cyan:hover {
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.4);
        }
    </style>
    
    @livewireStyles
</head>
<body class="h-full bg-surface text-text-primary overflow-hidden">
    {{ $slot }}
    
    @livewireScripts
    
    <script>
        // Auto-scroll chat to bottom
        document.addEventListener('DOMContentLoaded', function() {
            const chatContent = document.querySelector('#chat-output');
            if (chatContent) {
                chatContent.scrollTop = chatContent.scrollHeight;
            }
        });

        // Auto-scroll on new messages (Livewire updates)
        document.addEventListener('livewire:update', function() {
            const chatContent = document.querySelector('#chat-output');
            if (chatContent) {
                chatContent.scrollTop = chatContent.scrollHeight;
            }
        });
    </script>
</body>
</html>