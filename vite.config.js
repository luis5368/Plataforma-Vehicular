import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from 'tailwindcss';
import autoprefixer from 'autoprefixer';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),

    ],
    css: { postcss: { plugins: [tailwindcss({content: ['./resources/**/*.blade.php','./resources/**/*.js','./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php'], theme: {extend: {}}, plugins: []}), autoprefixer()] } },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
