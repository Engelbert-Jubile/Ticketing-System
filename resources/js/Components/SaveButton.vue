<template>
  <button
    :type="type"
    class="app-save-button"
    :style="{ '--save-button-background': headerBackground }"
    :disabled="disabled || processing"
    :aria-busy="processing"
  >
    <svg v-if="processing" class="save-spinner" viewBox="0 0 24 24" fill="none" aria-hidden="true">
      <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity="0.3" />
      <path d="M12 3a9 9 0 0 1 9 9" stroke="currentColor" stroke-width="3" stroke-linecap="round" />
    </svg>
    <svg v-else viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
      <path d="M17 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7l-4-4ZM7 5h8v5H7V5Zm10 14H7v-6h10v6Z" />
    </svg>
    <span aria-live="polite"><template v-if="processing">{{ $t('Menyimpan...') }}</template><slot v-else /></span>
  </button>
</template>

<script setup>
import { inject } from 'vue';

defineProps({
  processing: { type: Boolean, default: false },
  disabled: { type: Boolean, default: false },
  type: { type: String, default: 'submit' },
});

const headerBackground = inject('saveButtonBackground', 'linear-gradient(120deg, #2563eb 0%, #4338ca 45%, #1d4ed8 100%)');
</script>

<style scoped>
.app-save-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  border: 1px solid transparent;
  border-radius: 0.75rem;
  padding: 0.625rem 1.25rem;
  background: var(--save-button-background) !important;
  color: #fff !important;
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.5;
  box-shadow: 0 3px 8px #0f172a20;
  transition: filter 0.15s, box-shadow 0.15s;
}
.app-save-button:not(:disabled):hover { filter: brightness(1.08); box-shadow: 0 4px 12px #0f172a30; }
.app-save-button:focus-visible { outline: 2px solid var(--user-accent-color, #4338ca); outline-offset: 3px; }
.app-save-button:disabled { cursor: not-allowed; opacity: 0.65; }
.app-save-button[aria-busy="true"] { cursor: wait; }
.app-save-button svg { width: 1.125rem; height: 1.125rem; flex-shrink: 0; color: #fff !important; }
.save-spinner { animation: save-spin 0.8s linear infinite; }
@keyframes save-spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) { .save-spinner { animation: none; } }
</style>
