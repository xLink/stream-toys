import { fileURLToPath, URL } from 'url';
import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default ({ mode }) => {
  if (mode === 'development') {
    mode = '';
  }
  process.env = {...process.env, ...loadEnv(mode, process.cwd(), '')};

  return defineConfig({
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        '~': fileURLToPath(new URL('./node_modules', import.meta.url)),
      },
      dedupe: ['vue-demi'],

    },

    plugins: [
      laravel({
        input: 'resources/js/app.js',
        ssr: 'resources/js/ssr.js',
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
      host: process.env.VITE_HMR_URL,
      port: process.env.VITE_HMR_PORT,
      watch: {
        ignored: ['**/vendor/**', '**/storage/**'],
      },
      cors: true,
    },
  });
};
    
