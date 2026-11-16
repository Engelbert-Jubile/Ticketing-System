export const normalizeAppearance = value => value === 'glassy' ? 'glassy' : 'default';
export function normalizeThemePreferences(value) {
  return { appearance: normalizeAppearance(value?.appearance), color_mode: value?.color_mode === 'dark' ? 'dark' : 'light' };
}
export function themeStorageKey(userId) {
  const id = Number(userId);
  return Number.isSafeInteger(id) && id > 0 ? `tickora:theme:user:${id}` : null;
}
export function cacheThemePreferences(userId, preferences, storage) {
  const key = themeStorageKey(userId);
  if (!key) return;
  try { (storage ?? window.localStorage).setItem(key, JSON.stringify(normalizeThemePreferences(preferences))); } catch { /* The database remains the source of truth. */ }
}
export function applyThemePreferences(preferences, root = document.documentElement) {
  const value = normalizeThemePreferences(preferences);
  root.dataset.appearance = value.appearance;
  root.classList.toggle('dark', value.color_mode === 'dark');
  root.classList.toggle('light', value.color_mode === 'light');
  root.style.colorScheme = value.color_mode;
  return value;
}
