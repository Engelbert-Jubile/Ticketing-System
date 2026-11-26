import { computed, inject, reactive } from 'vue';
import { messages } from './messages';
import englishUi from '../../../lang/en.json';
import indonesianUi from '../../../lang/id.json';

const activeLocale = reactive({ value: 'en' });
export const uiLocale = computed(() => activeLocale.value === 'id' ? 'id-ID' : 'en-US');

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

export const translateUi = (key, params = {}) => {
  const current = activeLocale.value;
  const catalogue = current === 'id' ? indonesianUi : englishUi;
  const value = catalogue[key] ?? resolveFromPath(messages[current], key) ?? resolveFromPath(messages.en, key) ?? key;
  return interpolate(value, params);
};

export const createI18n = (initialLocale = 'en') => {
  const setLocale = value => {
    if (value !== 'en' && value !== 'id') return;
    activeLocale.value = value;
    if (typeof document !== 'undefined') {
      document.documentElement.setAttribute('lang', value);
    }
  };
  setLocale(initialLocale === 'id' ? 'id' : 'en');

  const currentLocale = computed(() => activeLocale.value);

  const install = app => {
    const api = {
      locale: currentLocale,
      setLocale,
      t: translateUi,
    };

    app.provide(I18N_SYMBOL, api);
    app.config.globalProperties.$t = api.t;
  };

  return {
    install,
    setLocale,
    getLocale: () => activeLocale.value,
    t: translateUi,
  };
};

export const useI18n = () => {
  const instance = inject(I18N_SYMBOL, null);
  if (instance) return instance;
  const fallback = {
    locale: computed(() => activeLocale.value),
    setLocale: () => {},
    t: translateUi,
  };
  return fallback;
};
