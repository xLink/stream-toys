import { createInertiaApp } from '@inertiajs/vue3';
import createServer from '@inertiajs/vue3/server';
import { renderToString } from '@vue/server-renderer';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createSSRApp, h } from 'vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';
import emitter from 'tiny-emitter/instance';
import GlobalComponents from './global.js';
import Store from './Stores';
import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon, FontAwesomeLayers } from '@fortawesome/vue-fontawesome';

import {
  faBars, faBarsStaggered, faRetweet, faFloppyDisk, faArrowsRotate,
  faTrash as fasTrash, 
} from '@fortawesome/free-solid-svg-icons';

library.add(
  faRetweet, faBars, faBarsStaggered, faFloppyDisk, faArrowsRotate, fasTrash
);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const EventBus = {
  $on: (...args) => emitter.on(...args),
  $once: (...args) => emitter.once(...args),
  $off: (...args) => emitter.off(...args),
  $emit: (...args) => emitter.emit(...args)
};

createServer((page) =>
  createInertiaApp({
    page,
    render: renderToString,
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
      resolvePageComponent(
        `./${name}.vue`,
        import.meta.glob('./**/*.vue'),
      ),
    setup({ App, props, plugin }) {
      let VueApp = createSSRApp({ render: () => h(App, props) });

      VueApp.config.globalProperties.$eventBus = EventBus;
      VueApp
        .use(plugin)
        .use(Store)
        .use(ZiggyVue, {
          ...page.props.ziggy,
          location: new URL(page.props.ziggy.location),
        })
        .use(GlobalComponents)
        .component('fa', FontAwesomeIcon)
        .component('FaLayers', FontAwesomeLayers)
      ;      

      return VueApp;
    },
  }),
);
