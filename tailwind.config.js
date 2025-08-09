import tailwindTheme from '@chks/vue/assets/tailwindTheme.js';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js}',
        './node_modules/@chks/vue/dist/**/*.js',
    ],

    theme: {
        extend: tailwindTheme,
    },

    plugins: [forms],
};
