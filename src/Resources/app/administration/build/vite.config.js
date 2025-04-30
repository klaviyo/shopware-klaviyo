import { defineConfig } from 'vite';
import svgr from 'vite-plugin-svgr';
import path from 'path';

export default defineConfig({
    plugins: [
        svgr({
            include: '**/*.svg', 
            svgrOptions: {
                exportType: 'named',
                svgo: false, 
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'src'),
        },
    },
    build: {
        rollupOptions: {
            input: {
                main: path.resolve(__dirname, 'src/main.js'), 
            },
        },
    },
    assetsInclude: ['**/*.svg'],
    optimizeDeps: {
        include: ['vue'], 
    },
});