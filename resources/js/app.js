import './bootstrap';
import '../css/app.css';
import '../css/header.css';
import '../css/header-fixes.css';
import '../css/animations.css';
import { createApp, h } from 'vue';
import { createInertiaApp, router } from '@inertiajs/vue3';
import { ZiggyVue } from 'ziggy-js/dist/vue.m';
import { Ziggy } from './ziggy';
import AppLayout from './Layouts/AppLayout.vue';
import { createI18n } from './i18n';

const pages = import.meta.glob('./Pages/**/*.vue');

createInertiaApp({
  resolve: name => {
    const importPage = pages[`./Pages/${name}.vue`];
    if (!importPage) {
      throw new Error(`Inertia page not found: ${name}`);
    }

    return importPage()
      .then(module => {
        const page = module.default ?? module;
        if (!page.layout) {
          page.layout = AppLayout;
        }
        return page;
      })
      .catch(error => {
        const message = error?.message ?? '';
        const isChunkError = error?.name === 'ChunkLoadError' || /ChunkLoadError|dynamically imported module/i.test(message);

        if (isChunkError && typeof window !== 'undefined') {
          console.warn('Inertia page load failed, performing hard reload to recover.', error);
          window.location.reload();
        }

        throw error;
      });
  },
  setup({ el, App, props, plugin }) {
    const inertiaApp = createApp({ render: () => h(App, props) });
    const detectLocaleFromUrl = () => {
      if (typeof window === 'undefined') return '';
      const match = window.location.pathname.match(/^\/(en|id)\b/);
      return match ? match[1] : '';
    };

    const initialLocale = props?.initialPage?.props?.locale
      || props?.page?.props?.locale
      || detectLocaleFromUrl()
      || 'en';
    const i18n = createI18n(initialLocale);
    Ziggy.defaults = { locale: initialLocale };

    const wrapRouteWithLocale = () => {
      if (typeof window === 'undefined' || typeof window.route !== 'function') return;
      if (window.route.__localeWrapped) return;
      const original = window.route;
      window.route = (name, params = {}, absolute, config) => {
        const locale = detectLocaleFromUrl();
        if (locale && params && typeof params === 'object' && !('locale' in params)) {
          return original(name, { locale, ...params }, absolute, config);
        }
        return original(name, params, absolute, config);
      };
      window.route.__localeWrapped = true;
    };

    inertiaApp
      .use(plugin)
      .use(ZiggyVue, Ziggy)
      .use(i18n)
      .mount(el);

    wrapRouteWithLocale();

    router.on('success', event => {
      const newLocale = event?.detail?.page?.props?.locale || detectLocaleFromUrl();
      if (newLocale) {
        i18n.setLocale(newLocale);
        Ziggy.defaults = { locale: newLocale };
      }
      wrapRouteWithLocale();
    });
  },
});

const finishProgress = () => document.body.classList.remove('is-inertia-loading');

// expose router globally for legacy scripts
if (typeof window !== 'undefined') {
  window.Inertia = router;
}

router.on('start', () => {
  document.body.classList.add('is-inertia-loading');
});

router.on('finish', finishProgress);
router.on('error', event => {
  finishProgress();
  const status = event?.detail?.response?.status;
  if (status === 403) {
    window.location.assign('/403');
  }
});
router.on('cancel', finishProgress);
