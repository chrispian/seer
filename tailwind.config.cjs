/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
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
            },
        },
    },
    plugins: [],
}
