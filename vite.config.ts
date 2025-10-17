import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.tsx',
                'resources/js/ui-builder-app.tsx',
            ],
            refresh: [
                'resources/views/**/*.blade.php',
                'vendor/hollis-labs/ui-builder/resources/views/**/*.blade.php', // triggers page reloads
            ],
        }),
        react({
            jsxRuntime: 'automatic',
        }),
    ],
    resolve: {
        dedupe: ['react', 'react-dom'],
    },

    // Configure HMR to be more resilient
    server: {
        hmr: {
            // Don't show error overlay - prevents white screen on HMR errors
            overlay: false,
        },
        // Watch options to prevent file descriptor issues
        watch: {
            ignored: ['**/node_modules/**', '**/storage/**', '**/vendor/**'],
        },
    },
    // Clear module cache on restart to prevent stale HMR state
    clearScreen: false,
})
