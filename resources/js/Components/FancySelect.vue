<template>
  <div ref="wrapperRef" class="relative w-full">
    <button
      type="button"
      class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-400 focus:outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
      :class="[open ? openButtonClass : '', accentButtonFocus]"
      :disabled="disabled"
      @click="toggle"
      @keydown.escape.prevent="close"
    >
      <span class="truncate">{{ selectedLabel }}</span>
      <span
        class="material-icons text-base text-slate-400 transition duration-200"
        :class="open ? iconOpenClass : ''"
      >
        expand_more
      </span>
    </button>
    <div
      v-if="open"
      class="absolute left-0 right-0 top-full mt-2 z-[9999] w-full rounded-xl rounded-t-none border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900"
      :class="panelClass"
    >
      <button
        v-for="option in options"
        :key="option.value"
        type="button"
        class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition"
        :class="optionClasses(option)"
        :disabled="option.disabled"
        @click="select(option)"
      >
        <span class="truncate">{{ option.label }}</span>
        <span v-if="String(option.value) === String(modelValue)" class="material-icons text-base">check</span>
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number, null], default: null },
  options: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
  accent: { type: String, default: 'indigo' },
});

const emit = defineEmits(['update:modelValue']);
const open = ref(false);
const wrapperRef = ref(null);

const accentMap = {
  indigo: {
    border: 'border-indigo-400',
    borderDark: 'dark:border-indigo-500',
    focus: 'focus:ring-indigo-200 dark:focus:ring-indigo-500/40',
    icon: 'rotate-180 text-indigo-500',
    selected: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200',
    hover: 'hover:border-indigo-400',
  },
  blue: {
    border: 'border-blue-400',
    borderDark: 'dark:border-blue-500',
    focus: 'focus:ring-blue-200 dark:focus:ring-blue-500/40',
    icon: 'rotate-180 text-blue-500',
    selected: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-200',
    hover: 'hover:border-blue-400',
  },
  emerald: {
    border: 'border-emerald-400',
    borderDark: 'dark:border-emerald-500',
    focus: 'focus:ring-emerald-200 dark:focus:ring-emerald-500/40',
    icon: 'rotate-180 text-emerald-500',
    selected: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200',
    hover: 'hover:border-emerald-400',
  },
  subtle: {
    border: 'border-slate-200',
    borderDark: 'dark:border-slate-700',
    focus: 'focus:ring-slate-200 dark:focus:ring-slate-700/40',
    icon: 'rotate-180 text-slate-500',
    selected: 'bg-slate-100 text-slate-700 dark:bg-slate-800/60 dark:text-slate-100',
    hover: 'hover:border-slate-300 dark:hover:border-slate-600',
  },
};

const accentConfig = computed(() => accentMap[props.accent] ?? accentMap.indigo);
const openButtonClass = computed(() => `${accentConfig.value.border} ${accentConfig.value.borderDark}`);
const accentButtonFocus = computed(() => accentConfig.value.focus);
const iconOpenClass = computed(() => accentConfig.value.icon);
const panelClass = computed(() => `${accentConfig.value.border} ${accentConfig.value.borderDark}`);

const selectedLabel = computed(() => {
  const current = props.options.find(option => String(option.value) === String(props.modelValue));
  return current?.label ?? 'Pilih opsi';
});

function toggle() {
  if (props.disabled) return;
  open.value = !open.value;
}

function close() {
  open.value = false;
}

function select(option) {
  if (option.disabled) return;
  emit('update:modelValue', option.value);
  close();
}

function optionClasses(option) {
  if (option.disabled) {
    return 'cursor-not-allowed text-slate-300 dark:text-slate-600';
  }
  if (String(option.value) === String(props.modelValue)) {
    return accentConfig.value.selected;
  }
  return 'text-slate-600 hover:bg-indigo-50 dark:text-slate-300 dark:hover:bg-indigo-500/10';
}

function handleOutsideClick(event) {
  if (wrapperRef.value && !wrapperRef.value.contains(event.target)) {
    close();
  }
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick);
});
</script>
