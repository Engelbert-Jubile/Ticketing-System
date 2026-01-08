import { computed, inject, reactive } from 'vue';
import { messages } from './messages';

const I18N_SYMBOL = Symbol('i18n');

const resolveFromPath = (obj, path) => {
  return path.split('.').reduce((acc, segment) => (acc && acc[segment] !== undefined ? acc[segment] : null), obj);
};

const interpolate = (template, params = {}) => {
  if (typeof template !== 'string') {
    return template;
  }
  return template.replace(/:([A-Za-z0-9_]+)/g, (_, key) => (params[key] !== undefined ? params[key] : `:${key}`));
};

const createTranslator = localeRef => {
  return (key, params = {}) => {
    const current = localeRef.value || 'en';
    const fallback = 'en';
    const currentMessages = messages[current] || messages[fallback] || {};
    const value = resolveFromPath(currentMessages, key) ?? resolveFromPath(messages[fallback] || {}, key) ?? key;
    return interpolate(value, params);
  };
};

export const createI18n = (initialLocale = 'en') => {
  const state = reactive({
    locale: initialLocale || 'en',
  });

  const setLocale = value => {
    if (typeof value === 'string' && value.trim() !== '') {
      state.locale = value;
      if (typeof document !== 'undefined') {
        document.documentElement.setAttribute('lang', value);
      }
    }
  };

  const currentLocale = computed(() => state.locale);
  const translator = computed(() => createTranslator(computed(() => state.locale)));

  const install = app => {
    const api = {
      locale: currentLocale,
      setLocale,
      t: (key, params) => translator.value(key, params),
    };

    app.provide(I18N_SYMBOL, api);
    app.config.globalProperties.$t = api.t;
  };

  return {
    install,
    setLocale,
    getLocale: () => state.locale,
    t: (key, params) => translator.value(key, params),
  };
};

export const useI18n = () => {
  const instance = inject(I18N_SYMBOL, null);
  if (instance) return instance;
  const fallback = {
    locale: computed(() => 'en'),
    setLocale: () => {},
    t: key => key,
  };
  return fallback;
};
