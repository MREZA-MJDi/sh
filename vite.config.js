import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/css/shared/typography.css',
                'resources/css/public-academy.css',
                'resources/js/app.js',
                'resources/css/student/app.css',
                'resources/css/parent/parent.css',
                'resources/css/teacher/teacher.css',
                'resources/js/student/student.js',
                'resources/js/parent/parent.js',
                'resources/js/teacher/teacher.js',
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
