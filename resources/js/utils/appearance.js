export const APPEARANCE_KEY = 'tickora:appearance';

export const normalizeAppearance = value => value === 'glassy' ? 'glassy' : 'default';

export function readAppearance(storage) {
  try { return normalizeAppearance((storage ?? window.localStorage).getItem(APPEARANCE_KEY)); }
  catch { return 'default'; }
}

export function applyAppearance(value, root = document.documentElement) {
  root.dataset.appearance = normalizeAppearance(value);
}

export function saveAppearance(value, storage) {
  const appearance = normalizeAppearance(value);
  try { (storage ?? window.localStorage).setItem(APPEARANCE_KEY, appearance); } catch { /* The selection still works when storage is unavailable. */ }
  return appearance;
}
