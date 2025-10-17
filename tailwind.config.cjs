/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.{ts,tsx}",
        './node_modules/@hollis-labs/ui-builder/**/*.{js,ts,jsx,tsx}', // ← include lib
    ],
    safelist: [
        {
            pattern: /mb-\[.*]/, // allows mb-[30px] and other dynamic margin-bottoms
            // pattern: /mt-\[.*\]/, // allows mb-[30px] and other dynamic margin-bottoms
        },
    ],
    theme: {
        extend: {
            colors: {
                'hot-pink': 'rgb(255 20 147)',
                'neon-cyan': 'rgb(0 255 255)',
                'electric-blue': 'rgb(0 191 255)',
                'deep-purple': '#4B0082',
                'bright-pink': '#FF69B4',
                surface: '#0f1419',
                'surface-2': '#1a1f2e',
                'surface-card': '#242b3d',
                'surface-elevated': '#2d3548',
                'text-primary': '#f8fafc',
                'text-secondary': '#cbd5e1',
                'text-muted': '#94a3b8',
            },
            fontFamily: {
                mono: ['JetBrains Mono', 'Monaco', 'Consolas', 'monospace'],
            },
            borderRadius: {
                pixel: '3px',
            },
            borderWidth: {
                thin: '0.5px',
            },
        },
    },
    plugins: [],
}
