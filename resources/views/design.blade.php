<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seer - Modern Interface Design</title>
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
</head>
<body class="h-full bg-surface text-text-primary overflow-hidden">
    <!-- Main 4-Column Layout -->
    <div class="h-screen flex">
        
        <!-- Left Column 1: Ribbon -->
        <div class="w-16 bg-surface-2 border-r border-thin border-hot-pink/30 flex flex-col items-center py-4">
            <!-- Fe Periodic Element -->
            <div class="relative">
                <!-- Main hot pink square -->
                <div class="w-10 h-10 bg-hot-pink rounded-pixel flex items-center justify-center relative z-10 pixel-card glow-pink">
                    <span class="text-white font-bold text-xl font-mono leading-none">Fe</span>
                </div>
                <!-- Offset outline -->
                <div class="absolute -top-0.5 -left-0.5 w-11 h-11 border-thin border-electric-blue rounded-pixel"></div>
            </div>
            
            <!-- Additional ribbon items -->
            <div class="mt-8 space-y-3">
                <button class="w-8 h-8 bg-surface-card rounded-pixel pixel-card border-thin border-hot-pink/30 flex items-center justify-center hover:bg-hot-pink/10 transition-colors glow-pink">
                    <svg class="w-4 h-4 text-hot-pink" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </button>
                <button class="w-8 h-8 bg-surface-card rounded-pixel pixel-card border-thin border-electric-blue/30 flex items-center justify-center hover:bg-electric-blue/10 transition-colors glow-blue">
                    <svg class="w-4 h-4 text-electric-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Left Column 2: Navigation -->
        <div class="w-72 bg-surface-2 border-r border-thin border-electric-blue/30 flex flex-col">
            <!-- Projects/Context Sessions -->
            <div class="p-4 border-b border-thin border-hot-pink/20">
                <div class="pixel-card pixel-card-pink p-3 glow-pink">
                    <h3 class="text-sm font-medium text-text-secondary mb-2">Active Project</h3>
                    <div class="bg-surface-elevated rounded-pixel p-2 pixel-card border-thin border-hot-pink/30">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-hot-pink">Seer Interface</span>
                            <span class="text-xs text-electric-blue">v2.0</span>
                        </div>
                        <div class="text-xs text-text-muted mt-1">Design System Rebuild</div>
                    </div>
                </div>
            </div>

            <!-- Chat List -->
            <div class="flex-1 p-4 overflow-y-auto">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-text-secondary">Recent Chats</h3>
                    <button class="text-xs text-text-muted hover:text-text-secondary">View All</button>
                </div>
                
                <div class="space-y-2">
                    <!-- Chat Items -->
                    <div class="pixel-card pixel-card-pink p-3 hover:bg-hot-pink/10 cursor-pointer transition-colors glow-pink">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-text-primary">Interface Design Discussion</div>
                                <div class="text-xs text-text-muted mt-1">Working on 4-column layout and card components...</div>
                            </div>
                            <div class="text-xs text-hot-pink ml-2 font-medium">2m</div>
                        </div>
                    </div>

                    <div class="pixel-card pixel-card-blue p-3 hover:bg-electric-blue/10 cursor-pointer transition-colors glow-blue">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-text-primary">Fragment Processing</div>
                                <div class="text-xs text-text-muted mt-1">Optimizing the enrichment pipeline...</div>
                            </div>
                            <div class="text-xs text-electric-blue ml-2 font-medium">1h</div>
                        </div>
                    </div>

                    <div class="pixel-card pixel-card-blue p-3 hover:bg-electric-blue/10 cursor-pointer transition-colors glow-blue">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-text-primary">API Documentation</div>
                                <div class="text-xs text-text-muted mt-1">Documenting the new endpoints...</div>
                            </div>
                            <div class="text-xs text-neon-cyan ml-2 font-medium">3h</div>
                        </div>
                    </div>

                    <div class="pixel-card pixel-card-blue p-3 hover:bg-electric-blue/10 cursor-pointer transition-colors glow-blue">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="text-sm font-medium text-text-primary">Database Optimization</div>
                                <div class="text-xs text-text-muted mt-1">Performance tuning for fragment queries...</div>
                            </div>
                            <div class="text-xs text-bright-pink ml-2 font-medium">1d</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- New Chat & Settings -->
            <div class="p-4 border-t border-thin border-electric-blue/20 space-y-3">
                <button class="w-full bg-hot-pink text-white py-2 px-4 rounded-pixel hover:bg-hot-pink/90 transition-colors text-sm font-medium pixel-card glow-pink">
                    New Chat
                </button>
                
                <button class="w-full bg-surface-card text-text-secondary py-2 px-4 rounded-pixel hover:bg-electric-blue/20 transition-colors text-sm font-medium flex items-center justify-center pixel-card border-thin border-electric-blue/40 glow-blue">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Settings
                </button>
            </div>
        </div>

        <!-- Middle Column: Chat Interface -->
        <div class="flex-1 flex flex-col bg-surface">
            <!-- Row 1: Header -->
            <div class="h-16 bg-surface-2 border-b border-thin border-hot-pink/30 flex items-center justify-between px-6 sticky top-0 z-10">
                <div class="flex items-center space-x-3">
                    <h2 class="text-lg font-medium text-text-primary">Interface Design Discussion</h2>
                    <span class="bg-neon-cyan/20 text-bright-pink text-xs px-2 py-1 rounded-pixel border-thin border-neon-cyan/40 font-medium">Active</span>
                </div>
                
                <div class="flex items-center space-x-2">
                    <button class="p-2 text-text-muted hover:text-hot-pink rounded-pixel hover:bg-hot-pink/20 border border-transparent hover:border-thin hover:border-hot-pink/40 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Row 2: Chat Content -->
            <div class="flex-1 p-6 overflow-y-auto space-y-4">
                <!-- Sample Messages -->
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">U</div>
                    <div class="flex-1">
                        <div class="bg-surface-card rounded-pixel p-4 pixel-card border-thin border-electric-blue/30 glow-blue">
                            <p class="text-text-primary">I'd like to create a modern 4-column layout for our chat interface. We need a ribbon, navigation, chat area, and widgets section.</p>
                        </div>
                        <div class="text-xs text-text-muted mt-1">2 minutes ago</div>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center text-white text-sm font-medium">A</div>
                    <div class="flex-1">
                        <div class="bg-surface-card rounded-pixel p-4 pixel-card border-thin border-electric-blue/30 glow-blue">
                            <p class="text-text-primary">Perfect! I'll help you create a clean, modern layout with card-based components. Let me break this down into the four columns you mentioned:</p>
                            <ul class="mt-2 space-y-1 text-sm text-text-secondary">
                                <li>• Ribbon with Fe periodic element</li>
                                <li>• Navigation with projects and chat list</li>
                                <li>• Main chat area with sticky header</li>
                                <li>• Widgets and bookmarks section</li>
                            </ul>
                        </div>
                        <div class="text-xs text-text-muted mt-1">1 minute ago</div>
                    </div>
                </div>

                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">U</div>
                    <div class="flex-1">
                        <div class="bg-surface-card rounded-pixel p-4 pixel-card border-thin border-electric-blue/30 glow-blue">
                            <p class="text-text-primary">That looks great! Can we start with a black, white, and grey color scheme to focus on the layout first?</p>
                        </div>
                        <div class="text-xs text-text-muted mt-1">30 seconds ago</div>
                    </div>
                </div>

                <!-- Typing Indicator -->
                <div class="flex items-start space-x-3">
                    <div class="w-8 h-8 bg-gray-700 rounded-full flex items-center justify-center text-white text-sm font-medium">A</div>
                    <div class="flex-1">
                        <div class="bg-surface-card rounded-pixel p-4 pixel-card border-thin border-electric-blue/40 glow-blue">
                            <div class="flex space-x-1">
                                <div class="w-2 h-2 bg-text-muted rounded-full animate-bounce"></div>
                                <div class="w-2 h-2 bg-text-muted rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                                <div class="w-2 h-2 bg-text-muted rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Input Area -->
            <div class="bg-surface-2 border-t border-thin border-hot-pink/30">
                <!-- System Notes Row (Hidden by default) -->
                <div class="px-6 py-2 bg-neon-cyan/10 border-b border-thin border-neon-cyan/30 text-sm text-deep-purple" style="display: none;">
                    Fragment logged. Tagged: design, interface. <button class="text-hot-pink underline hover:text-deep-purple">Edit?</button>
                </div>
                
                <!-- Chat Input -->
                <div class="p-4">
                    <div class="flex space-x-3">
                        <div class="flex-1">
                            <textarea 
                                class="w-full p-3 border-thin border-hot-pink/30 rounded-pixel resize-none focus:ring-2 focus:ring-hot-pink focus:border-hot-pink pixel-card" 
                                rows="2" 
                                placeholder="Type your message..."
                            ></textarea>
                        </div>
                        <button class="px-4 py-2 bg-hot-pink text-white rounded-pixel hover:bg-hot-pink/90 transition-colors self-center pixel-card glow-pink">
                            Send
                        </button>
                    </div>
                    
                    <!-- Text/Links area -->
                    <div class="mt-2 flex items-center justify-between text-sm text-gray-500">
                        <div class="flex space-x-4">
                            <button class="hover:text-hot-pink transition-colors">📎 Attach</button>
                            <button class="hover:text-electric-blue transition-colors">🔗 Link</button>
                            <button class="hover:text-neon-cyan transition-colors">💡 Fragment</button>
                        </div>
                        <div class="text-xs">
                            Shift + Enter for new line
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Widgets & Search -->
        <div class="w-80 bg-surface-2 border-l border-thin border-electric-blue/30 flex flex-col">
            <!-- Widgets Section -->
            <div class="flex-1 p-4 overflow-y-auto">
                <h3 class="text-sm font-medium text-text-secondary mb-4">Widgets</h3>
                
                <!-- System Widgets -->
                <div class="space-y-4 mb-6">
                    <!-- Stats Widget -->
                    <div class="pixel-card pixel-card-pink p-4 glow-pink">
                        <h4 class="text-sm font-medium text-hot-pink mb-3">Today's Activity</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-text-muted">Messages</span>
                                <span class="text-sm font-medium text-hot-pink">24</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-text-muted">Fragments</span>
                                <span class="text-sm font-medium text-electric-blue">12</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-text-muted">Tags Created</span>
                                <span class="text-sm font-medium text-neon-cyan">8</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions Widget -->
                    <div class="pixel-card pixel-card-blue p-4 glow-blue">
                        <h4 class="text-sm font-medium text-electric-blue mb-3">Quick Actions</h4>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="bg-surface-card p-2 rounded-pixel text-xs text-text-secondary hover:bg-hot-pink/20 border-thin border-hot-pink/40 pixel-card glow-pink">Export</button>
                            <button class="bg-surface-card p-2 rounded-pixel text-xs text-text-secondary hover:bg-electric-blue/20 border-thin border-electric-blue/40 pixel-card glow-blue">Search</button>
                            <button class="bg-surface-card p-2 rounded-pixel text-xs text-text-secondary hover:bg-neon-cyan/20 border-thin border-neon-cyan/40 pixel-card glow-cyan">Filter</button>
                            <button class="bg-surface-card p-2 rounded-pixel text-xs text-text-secondary hover:bg-deep-purple/20 border-thin border-deep-purple/40 pixel-card">Archive</button>
                        </div>
                    </div>

                    <!-- Recent Files Widget -->
                    <div class="pixel-card pixel-card-cyan p-4">
                        <h4 class="text-sm font-medium text-neon-cyan mb-3">Recent Files</h4>
                        <div class="space-y-2">
                            <div class="flex items-center space-x-2 text-xs">
                                <div class="w-2 h-2 bg-hot-pink rounded-full"></div>
                                <span class="text-text-secondary flex-1 truncate">design-system.md</span>
                                <span class="text-text-muted">2m</span>
                            </div>
                            <div class="flex items-center space-x-2 text-xs">
                                <div class="w-2 h-2 bg-electric-blue rounded-full"></div>
                                <span class="text-text-secondary flex-1 truncate">interface-specs.pdf</span>
                                <span class="text-text-muted">1h</span>
                            </div>
                            <div class="flex items-center space-x-2 text-xs">
                                <div class="w-2 h-2 bg-neon-cyan rounded-full"></div>
                                <span class="text-text-secondary flex-1 truncate">wireframes.figma</span>
                                <span class="text-text-muted">3h</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Widgets -->
                <div class="border-t border-gray-100 pt-4">
                    <h4 class="text-sm font-medium text-text-secondary mb-3">Custom Widgets</h4>
                    <div class="pixel-card pixel-card-pink p-4 text-center glow-pink">
                        <div class="text-hot-pink/50 mb-2">
                            <svg class="w-8 h-8 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                        </div>
                        <button class="text-xs text-text-muted hover:text-hot-pink">Add Widget</button>
                    </div>
                </div>
            </div>

            <!-- Search Results / Bookmarks Overlay Area -->
            <div class="border-t border-thin border-electric-blue/30 p-4 bg-surface">
                <div class="flex items-center justify-between mb-3">
                    <h4 class="text-sm font-medium text-electric-blue">Search Results</h4>
                    <button class="text-xs text-text-muted hover:text-electric-blue">Clear</button>
                </div>
                
                <div class="space-y-2 max-h-48 overflow-y-auto">
                    <div class="bg-surface-card p-3 rounded-pixel pixel-card border-thin border-hot-pink/30 hover:border-hot-pink cursor-pointer glow-pink">
                        <div class="text-sm font-medium text-text-primary mb-1">Component Architecture</div>
                        <div class="text-xs text-text-muted">Found in design-patterns.md</div>
                    </div>
                    
                    <div class="bg-surface-card p-3 rounded-pixel pixel-card border-thin border-electric-blue/30 hover:border-electric-blue cursor-pointer glow-blue">
                        <div class="text-sm font-medium text-text-primary mb-1">Layout Grid System</div>
                        <div class="text-xs text-text-muted">Found in css-framework.scss</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Simple interaction for testing
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Seer Design Interface Loaded');
            
            // Auto-scroll chat to bottom
            const chatContent = document.querySelector('.flex-1.p-6.overflow-y-auto');
            if (chatContent) {
                chatContent.scrollTop = chatContent.scrollHeight;
            }
        });
    </script>
</body>
</html>