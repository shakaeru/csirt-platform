import defaultTheme from 'tailwindcss/defaultTheme';

export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
        'node_modules/flowbite/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                csirt: {
                    primary: '#1260a5',
                    'primary-light': '#6b96bc',
                    'primary-lighter': '#235a87',
                    navy: '#073d53',
                    secondary: '#8ebd45',
                    'accent-lime': '#c5d73d',
                    'accent-red': '#af112d',
                    neutral: { 900: '#36343a', 600: '#6b7280', 100: '#f5f7fa' },
                },
            },
        },
    },
    plugins: [require('flowbite/plugin')],
};