import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            // Dash UI palette: slate-like grays and its signature indigo.
            colors: {
                gray: {
                    100: '#f1f5f9',
                    200: '#f4f6f8',
                    300: '#dfe3e8',
                    400: '#c4cdd5',
                    500: '#919eab',
                    600: '#637381',
                    700: '#454f5b',
                    800: '#212b36',
                    900: '#161c24',
                },
                indigo: {
                    500: '#7b68ff',
                    600: '#624bff',
                    700: '#5340d9',
                    800: '#4533b8',
                },
            },
        },
    },

    plugins: [forms],
};
