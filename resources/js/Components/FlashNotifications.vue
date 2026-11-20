<template>
  <Teleport to="body">
    <Transition name="flash-notice">
      <div
        v-if="notice"
        class="flash-notice"
        :class="`flash-notice--${notice.type}`"
        :role="notice.type === 'error' ? 'alert' : 'status'"
        aria-atomic="true"
      >
        <span class="material-icons flash-notice__icon" aria-hidden="true">{{ notice.type === 'success' ? 'check_circle' : notice.type === 'error' ? 'error' : 'warning' }}</span>
        <p>{{ $t(notice.message) }}</p>
        <button type="button" :aria-label="$t('Tutup')" @click="dismiss">
          <span class="material-icons" aria-hidden="true">close</span>
        </button>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({ flash: { type: Object, default: () => ({}) } });
const notice = ref(null);
let timer;

function dismiss() {
  clearTimeout(timer);
  notice.value = null;
}

// Watch each response so consecutive saves with the same message also notify.
watch(() => props.flash, flash => {
  dismiss();
  const type = ['error', 'warning', 'success'].find(key => typeof flash?.[key] === 'string' && flash[key].trim());
  if (!type) return;
  notice.value = { type, message: flash[type] };
  if (type === 'success') timer = setTimeout(dismiss, 6000);
}, { immediate: true });

onBeforeUnmount(dismiss);
</script>

<style scoped>
.flash-notice {
  position: fixed;
  top: 6rem;
  right: 1rem;
  z-index: 200;
  display: flex;
  align-items: flex-start;
  gap: 0.75rem;
  width: max-content;
  max-width: min(28rem, calc(100vw - 2rem));
  padding: 1rem;
  border: 1px solid;
  border-radius: 0.875rem;
  box-shadow: 0 12px 32px #0f172a26;
  font-size: 0.875rem;
  line-height: 1.5;
}
.flash-notice p { margin: 0; overflow-wrap: anywhere; }
.flash-notice--success { background: #ecfdf5; border-color: #6ee7b7; color: #065f46; }
.flash-notice--error { background: #fff1f2; border-color: #fda4af; color: #9f1239; }
.flash-notice--warning { background: #fffbeb; border-color: #fcd34d; color: #92400e; }
.flash-notice__icon { font-size: 1.25rem; margin-top: 0.125rem; }
.flash-notice button { display: flex; flex-shrink: 0; margin-left: 0.25rem; padding: 0.125rem; border-radius: 0.25rem; color: inherit; }
.flash-notice button .material-icons { font-size: 1.125rem; }
.flash-notice button:focus-visible { outline: 2px solid currentColor; outline-offset: 2px; }
.flash-notice-enter-active, .flash-notice-leave-active { transition: opacity 0.2s, transform 0.2s; }
.flash-notice-enter-from, .flash-notice-leave-to { opacity: 0; transform: translateY(-0.5rem); }
@media (prefers-reduced-motion: reduce) { .flash-notice-enter-active, .flash-notice-leave-active { transition: none; } }
</style>
