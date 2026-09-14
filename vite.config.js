// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
  resolve: {
    alias: {
      'ziggy-js/dist/vue.m': 'ziggy-js',
    },
  },
  plugins: [
    vue(),
    laravel({
      input: [
        'resources/css/app.css',
        'resources/css/animations.css',
        'resources/js/app.js',
        'resources/js/dashboard.js',
        'resources/js/legacy-entry.js',
      ],
      refresh: true
    })
  ]
});
