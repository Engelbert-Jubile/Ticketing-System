import { route as ziggyRoute } from 'ziggy-js';
import { Ziggy } from '../ziggy';

const callRoute = callback => {
  try {
    return callback();
  } catch (error) {
    if (typeof console !== 'undefined' && error) {
      console.warn('[resolveRoute] fallback to "#" for route:', error?.message ?? error);
    }
    return '#';
  }
};

const detectLocaleFromUrl = () => {
  if (typeof window === 'undefined') return '';
  const match = window.location.pathname.match(/^\/(en|id)\b/);
  return match ? match[1] : '';
};

const withLocale = params => {
  if (!params || typeof params !== 'object' || Array.isArray(params)) {
    return params;
  }
  if ('locale' in params) {
    return params;
  }
  const locale = detectLocaleFromUrl();
  return locale ? { locale, ...params } : params;
};

const fallback = (name, params, absolute, config) => {
  const finalConfig = config ? { ...Ziggy, ...config } : Ziggy;
  return callRoute(() => ziggyRoute(name, withLocale(params), absolute, finalConfig));
};

export const resolveRoute = (name, params = {}, absolute, config) => {
  if (typeof window !== 'undefined' && typeof window.route === 'function') {
    return callRoute(() => window.route(name, withLocale(params), absolute, config));
  }
  return fallback(name, params, absolute, config);
};

export default resolveRoute;
