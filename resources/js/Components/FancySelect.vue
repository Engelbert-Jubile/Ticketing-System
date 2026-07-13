<template>
  <div ref="wrapperRef" class="relative w-full">
    <button
      type="button"
      role="combobox"
      aria-haspopup="listbox"
      :aria-label="ariaLabel"
      :aria-expanded="open"
      :aria-controls="listboxId"
      :aria-activedescendant="open && activeIndex >= 0 ? optionId(activeIndex) : undefined"
      class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-400 focus:outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
      :class="[open ? openButtonClass : '', accentButtonFocus]"
      :disabled="disabled"
      @click="toggle"
      @keydown="handleKeydown"
    >
      <span class="truncate">{{ selectedLabel }}</span>
      <span
        class="material-icons text-base text-slate-400 transition duration-200"
        :class="open ? iconOpenClass : ''"
      >
        expand_more
      </span>
    </button>
    <Transition
      enter-active-class="transition duration-150 ease-out"
      enter-from-class="scale-95 opacity-0"
      enter-to-class="scale-100 opacity-100"
      leave-active-class="transition duration-100 ease-in"
      leave-from-class="scale-100 opacity-100"
      leave-to-class="scale-95 opacity-0"
    >
      <div
        v-if="open"
        :id="listboxId"
        role="listbox"
        class="absolute left-0 right-0 z-[9999] w-full overflow-y-auto overscroll-contain rounded-xl border border-slate-200 bg-white p-2 shadow-2xl dark:border-slate-700 dark:bg-slate-900"
        :class="[panelClass, openUp ? 'bottom-full mb-2 origin-bottom' : 'top-full mt-2 origin-top']"
        :style="{ maxHeight: panelMaxHeight + 'px' }"
      >
        <button
          v-for="(option, index) in options"
          :id="optionId(index)"
          :key="option.value"
          type="button"
          role="option"
          class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left text-sm transition"
          :class="optionClasses(option, index)"
          :aria-selected="String(option.value) === String(modelValue)"
          :disabled="option.disabled"
          @mouseenter="activeIndex = index"
          @click="select(option)"
        >
          <span class="truncate">{{ option.label }}</span>
          <span v-if="String(option.value) === String(modelValue)" class="material-icons text-base">check</span>
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number, null], default: null },
  options: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
  accent: { type: String, default: 'indigo' },
  ariaLabel: { type: String, default: 'Pilih opsi' },
});

const emit = defineEmits(['update:modelValue']);
const open = ref(false);
const wrapperRef = ref(null);
const activeIndex = ref(-1);
const openUp = ref(false);
const panelMaxHeight = ref(240);
const listboxId = 'fancy-select-' + Math.random().toString(36).slice(2, 10);

const accentMap = {
  indigo: {
    border: 'border-indigo-400',
    borderDark: 'dark:border-indigo-500',
    focus: 'focus:ring-indigo-200 dark:focus:ring-indigo-500/40',
    icon: 'rotate-180 text-indigo-500',
    selected: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200',
    active: 'bg-indigo-50/70 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200',
    hover: 'hover:border-indigo-400',
  },
  blue: {
    border: 'border-blue-400',
    borderDark: 'dark:border-blue-500',
    focus: 'focus:ring-blue-200 dark:focus:ring-blue-500/40',
    icon: 'rotate-180 text-blue-500',
    selected: 'bg-blue-50 text-blue-700 dark:bg-blue-500/10 dark:text-blue-200',
    active: 'bg-blue-50/70 text-blue-700 dark:bg-blue-500/10 dark:text-blue-200',
    hover: 'hover:border-blue-400',
  },
  emerald: {
    border: 'border-emerald-400',
    borderDark: 'dark:border-emerald-500',
    focus: 'focus:ring-emerald-200 dark:focus:ring-emerald-500/40',
    icon: 'rotate-180 text-emerald-500',
    selected: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200',
    active: 'bg-emerald-50/70 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200',
    hover: 'hover:border-emerald-400',
  },
  subtle: {
    border: 'border-slate-200',
    borderDark: 'dark:border-slate-700',
    focus: 'focus:ring-slate-200 dark:focus:ring-slate-700/40',
    icon: 'rotate-180 text-slate-500',
    selected: 'bg-slate-100 text-slate-700 dark:bg-slate-800/60 dark:text-slate-100',
    active: 'bg-slate-100 text-slate-700 dark:bg-slate-800/60 dark:text-slate-100',
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
  open.value ? close() : openMenu();
}

function openMenu() {
  open.value = true;
  const selected = props.options.findIndex(option => !option.disabled && String(option.value) === String(props.modelValue));
  activeIndex.value = selected >= 0 ? selected : props.options.findIndex(option => !option.disabled);
  nextTick(updatePlacement);
}

function close() {
  open.value = false;
  activeIndex.value = -1;
}

function select(option) {
  if (option.disabled) return;
  emit('update:modelValue', option.value);
  close();
}

function optionClasses(option, index) {
  if (option.disabled) {
    return 'cursor-not-allowed text-slate-300 dark:text-slate-600';
  }
  if (String(option.value) === String(props.modelValue)) {
    return accentConfig.value.selected;
  }
  if (index === activeIndex.value) {
    return accentConfig.value.active;
  }
  return 'text-slate-600 hover:bg-indigo-50 dark:text-slate-300 dark:hover:bg-indigo-500/10';
}

function optionId(index) {
  return listboxId + '-option-' + index;
}

function moveActive(step) {
  const enabled = props.options
    .map((option, index) => option.disabled ? -1 : index)
    .filter(index => index >= 0);
  if (!enabled.length) return;
  const current = enabled.indexOf(activeIndex.value);
  const next = current < 0 ? 0 : (current + step + enabled.length) % enabled.length;
  activeIndex.value = enabled[next];
}

function handleKeydown(event) {
  if (event.key === 'Escape') {
    event.preventDefault();
    close();
    return;
  }
  if (event.key === 'Tab') {
    close();
    return;
  }
  if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
    event.preventDefault();
    if (!open.value) openMenu();
    else moveActive(event.key === 'ArrowDown' ? 1 : -1);
    return;
  }
  if (event.key === 'Home' || event.key === 'End') {
    event.preventDefault();
    if (!open.value) openMenu();
    const indexes = props.options.map((option, index) => option.disabled ? -1 : index).filter(index => index >= 0);
    activeIndex.value = event.key === 'Home' ? indexes[0] : indexes[indexes.length - 1];
    return;
  }
  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault();
    if (!open.value) openMenu();
    else if (activeIndex.value >= 0) select(props.options[activeIndex.value]);
  }
}

function updatePlacement() {
  if (!open.value || !wrapperRef.value) return;
  const rect = wrapperRef.value.getBoundingClientRect();
  const desiredHeight = Math.min(240, props.options.length * 42 + 16);
  const below = window.innerHeight - rect.bottom;
  const above = rect.top;
  openUp.value = below < desiredHeight + 12 && above > below;
  const available = openUp.value ? above : below;
  panelMaxHeight.value = Math.max(48, Math.min(240, available - 12));
}

function handleOutsideClick(event) {
  if (wrapperRef.value && !wrapperRef.value.contains(event.target)) {
    close();
  }
}

onMounted(() => {
  document.addEventListener('click', handleOutsideClick);
  window.addEventListener('resize', updatePlacement);
  window.addEventListener('scroll', updatePlacement, true);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick);
  window.removeEventListener('resize', updatePlacement);
  window.removeEventListener('scroll', updatePlacement, true);
});

watch(() => props.modelValue, () => {
  if (!open.value) return;
  activeIndex.value = props.options.findIndex(option => !option.disabled && String(option.value) === String(props.modelValue));
});
</script>
