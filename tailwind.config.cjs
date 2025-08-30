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
        extend: {},
    },
    plugins: [],
}
