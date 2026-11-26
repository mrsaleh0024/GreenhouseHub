import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            colors: {
                white: 'rgb(var(--theme-white) / <alpha-value>)',
                gray: {
                    50: 'rgb(var(--theme-background) / <alpha-value>)',
                    100: 'rgb(var(--theme-background) / <alpha-value>)',
                    200: 'rgb(var(--theme-text) / 0.12)',
                    300: 'rgb(var(--theme-text) / 0.25)',
                    400: 'rgb(var(--theme-text) / 0.65)',
                    500: 'rgb(var(--theme-text) / 0.8)',
                    600: 'rgb(var(--theme-text) / 0.9)',
                    700: 'rgb(var(--theme-text) / <alpha-value>)',
                    800: 'rgb(var(--theme-text) / <alpha-value>)',
                    900: 'rgb(var(--theme-text) / <alpha-value>)',
                },
                green: {
                    50: 'rgb(var(--theme-primary) / 0.08)',
                    100: 'rgb(var(--theme-primary) / 0.12)',
                    600: 'rgb(var(--theme-primary) / <alpha-value>)',
                    700: 'rgb(var(--theme-primary-dark) / <alpha-value>)',
                    800: 'rgb(var(--theme-primary-dark) / <alpha-value>)',
                },
                indigo: {
                    50: 'rgb(var(--theme-primary) / 0.08)',
                    100: 'rgb(var(--theme-primary) / 0.12)',
                    400: 'rgb(var(--theme-primary) / <alpha-value>)',
                    500: 'rgb(var(--theme-primary) / <alpha-value>)',
                    600: 'rgb(var(--theme-primary) / <alpha-value>)',
                    700: 'rgb(var(--theme-primary-dark) / <alpha-value>)',
                    800: 'rgb(var(--theme-primary-dark) / <alpha-value>)',
                },
            },
            fontFamily: {
                sans: ['Vazirmatn', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
