<template>
  <span
    :class="pill.class"
    :style="pill.style"
    v-bind="pill.attrs"
  >
    <slot>
      {{ displayLabel }}
    </slot>
  </span>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { formatStatusLabel, statusPillProps } from '@/utils/statusTheme';

const props = defineProps({
  status: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  variant: { type: String, default: 'status' },
  size: { type: String, default: 'md' },
});

const isDark = ref(false);
let observer;
let mediaQuery;

const updateIsDark = () => {
  if (typeof document === 'undefined') {
    isDark.value = false;
    return;
  }

  const root = document.documentElement;
  if (root.classList.contains('dark')) {
    isDark.value = true;
    return;
  }
  if (root.classList.contains('light')) {
    isDark.value = false;
    return;
  }

  if (typeof window !== 'undefined' && window.matchMedia) {
    isDark.value = window.matchMedia('(prefers-color-scheme: dark)').matches;
  } else {
    isDark.value = false;
  }
};

onMounted(() => {
  updateIsDark();
  if (typeof document !== 'undefined' && typeof MutationObserver !== 'undefined') {
    observer = new MutationObserver(updateIsDark);
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });
  }
  if (typeof window !== 'undefined' && window.matchMedia) {
    mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
    if (mediaQuery.addEventListener) {
      mediaQuery.addEventListener('change', updateIsDark);
    } else if (mediaQuery.addListener) {
      mediaQuery.addListener(updateIsDark);
    }
  }
});

onBeforeUnmount(() => {
  if (observer) observer.disconnect();
  if (mediaQuery) {
    if (mediaQuery.removeEventListener) {
      mediaQuery.removeEventListener('change', updateIsDark);
    } else if (mediaQuery.removeListener) {
      mediaQuery.removeListener(updateIsDark);
    }
  }
});

const pill = computed(() => {
  const base = statusPillProps(props.status, { variant: props.variant, size: props.size });

  if (props.variant === 'sla' && isDark.value) {
    const overrides = {
      default: {
        bg: 'rgba(30, 41, 59, 0.7)',
        color: '#e2e8f0',
        hoverBg: 'rgba(51, 65, 85, 0.9)',
        hoverColor: '#ffffff',
        ring: 'rgba(148, 163, 184, 0.35)',
      },
      met: {
        bg: 'rgba(16, 185, 129, 0.28)',
        color: '#ecfdf3',
        hoverBg: 'rgba(16, 185, 129, 0.5)',
        hoverColor: '#ffffff',
        ring: 'rgba(16, 185, 129, 0.5)',
      },
      breached: {
        bg: 'rgba(239, 68, 68, 0.3)',
        color: '#ffe4e6',
        hoverBg: 'rgba(239, 68, 68, 0.52)',
        hoverColor: '#ffffff',
        ring: 'rgba(248, 113, 113, 0.55)',
      },
      pending: {
        bg: 'rgba(245, 158, 11, 0.28)',
        color: '#fef3c7',
        hoverBg: 'rgba(245, 158, 11, 0.5)',
        hoverColor: '#ffffff',
        ring: 'rgba(245, 158, 11, 0.55)',
      },
    };

    const key = base.attrs?.['data-status'] ?? 'default';
    const theme = overrides[key] || overrides.default;
    base.style = {
      ...base.style,
      '--pill-bg': theme.bg,
      '--pill-color': theme.color,
      '--pill-hover-bg': theme.hoverBg,
      '--pill-hover-color': theme.hoverColor,
      '--pill-ring': theme.ring,
    };
  }

  return base;
});
const displayLabel = computed(() => formatStatusLabel(props.status, props.label));
</script>

<style>
.status-pill {
  --pill-bg: #e2e8f0;
  --pill-color: #334155;
  --pill-hover-bg: #cbd5e1;
  --pill-hover-color: #0f172a;
  --pill-ring: rgba(148, 163, 184, 0.35);
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  border-radius: 9999px;
  font-weight: 600;
  line-height: 1.4;
  text-transform: capitalize;
  background: var(--pill-bg);
  color: var(--pill-color);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--pill-color), transparent 78%);
  transition: background-color 0.18s ease, color 0.18s ease, box-shadow 0.18s ease, transform 0.18s ease;
}

.status-pill:hover {
  background: var(--pill-hover-bg);
  color: var(--pill-hover-color);
  box-shadow: 0 0 0 2px var(--pill-ring), 0 10px 18px -12px rgba(15, 23, 42, 0.25);
  transform: translateY(-1px);
}

.dark .status-pill:not([data-variant='sla']) {
  background: color-mix(in srgb, var(--pill-bg), #0f172a 65%);
  color: color-mix(in srgb, var(--pill-color), #ffffff 70%);
  box-shadow: 0 0 0 1px color-mix(in srgb, var(--pill-color), #ffffff 60%);
}

.dark .status-pill:not([data-variant='sla']):hover {
  background: color-mix(in srgb, var(--pill-hover-bg), #0f172a 55%);
  color: color-mix(in srgb, var(--pill-hover-color), #ffffff 70%);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--pill-ring), #ffffff 35%), 0 10px 18px -12px rgba(2, 6, 23, 0.6);
}

.status-pill--sm { padding: 0.2rem 0.55rem; font-size: 0.7rem; }
.status-pill--md { padding: 0.35rem 0.75rem; font-size: 0.75rem; }
.status-pill--lg { padding: 0.5rem 1rem; font-size: 0.85rem; }
</style>
