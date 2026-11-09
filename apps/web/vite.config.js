import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'
export default defineConfig({ plugins:[vue()], server: {
    proxy: {
        '/api': {
            target: 'https://dashboard.sami-care.sa',
            changeOrigin: true,
            secure: false,
        },
    },
}, resolve:{alias:{'@':fileURLToPath(new URL('./src',import.meta.url))}} })
