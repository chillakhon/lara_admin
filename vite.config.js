import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    server: {
        host: '0.0.0.0',
        hmr: {
            host: 'localhost'
        },
        port: 5174
    },
    build: {
        outDir: path.resolve(__dirname, 'public/build'),
        manifest: true,
        rollupOptions: {
            output: {
                chunkFileNames: 'build/assets/[name]-[hash].js',
                entryFileNames: 'build/assets/[name]-[hash].js',
                assetFileNames: 'build/assets/[name]-[hash].[ext]',
            },
        },
    },
});
