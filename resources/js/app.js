import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import emitter from 'tiny-emitter/instance';
import GlobalComponents from './global.js';
import CHKSVue from '@chks/vue';
import Store from './Stores';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const EventBus = {
  $on: (...args) => emitter.on(...args),
  $once: (...args) => emitter.once(...args),
  $off: (...args) => emitter.off(...args),
  $emit: (...args) => emitter.emit(...args)
};

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) =>
    resolvePageComponent(
      `./${name}.vue`,
      import.meta.glob('./**/*.vue'),
    ),
  setup({ el, App, props, plugin }) {
    let VueApp = createApp({ render: () => h(App, props) });
    
    VueApp.config.globalProperties.$eventBus = EventBus;
    VueApp
      .use(plugin)
      .use(GlobalComponents)
      .use(Store)
      .use(CHKSVue)
      .use(ZiggyVue)
      .mount(el)
    ;

    return VueApp;
  },
  progress: {
      color: '#4B5563',
  },
});
