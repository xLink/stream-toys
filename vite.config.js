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
    define: {
      __VUE_PROD_DEVTOOLS__: true,
    },
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

    build: {
      rollupOptions: {
        output: {
          manualChunks(id) {
            if (id.includes('node_modules')) {
              if (id.includes('@fortawesome')) return 'vendor-fontawesome';
              if (id.includes('vue') || id.includes('@vue') || id.includes('@inertiajs')) return 'vendor-vue';
              if (id.includes('pusher') || id.includes('laravel-echo')) return 'vendor-echo';
              return 'vendor';
            }
          },
        },
      },
    },

    server: {
      host: '0.0.0.0',
      port: process.env.VITE_HMR_PORT,
      hmr: {
        host: process.env.VITE_HMR_URL,
      },
      watch: {
        ignored: ['**/vendor/**', '**/storage/**'],
      },
      cors: true,
    },
  });
};
    
