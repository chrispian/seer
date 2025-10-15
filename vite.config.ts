import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.tsx',
                'resources/js/v2-app.tsx',
            ],
            refresh: true,
        }),
        react({
            jsxRuntime: 'automatic',
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    optimizeDeps: {
        include: ['react', 'react-dom'],
        // Force re-optimization when dependencies change
        force: false,
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
