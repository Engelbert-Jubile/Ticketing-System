<template>
  <div class="relative" ref="root">
    <button
      type="button"
      class="multiselect-trigger"
      @click.stop="toggle"
      @keydown.enter.prevent="toggle"
      @keydown.space.prevent="toggle"
      @keydown.escape.stop.prevent="close"
      :aria-expanded="open"
    >
      <span v-if="selectedLabels.length" class="truncate text-left">
        {{ summary }}
      </span>
      <span v-else class="text-slate-400">{{ placeholder }}</span>
      <span class="material-icons text-base text-slate-400">expand_more</span>
    </button>

    <Transition name="dropdown-fade">
      <div v-if="open" class="multiselect-panel" role="menu">
        <div class="max-h-56 overflow-y-auto py-2">
          <label
            v-for="option in options"
            :key="option.value"
            class="flex cursor-pointer items-center justify-between px-3 py-2 text-sm hover:bg-slate-100 dark:hover:bg-slate-700/50"
          >
            <div class="flex items-center gap-2">
              <input
                type="checkbox"
                class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                :value="option.value"
                :checked="internal.has(option.value)"
                @change="toggleOption(option.value)"
              />
              <span>{{ option.label }}</span>
            </div>
            <span v-if="internal.has(option.value)" class="material-icons text-base text-blue-500">check</span>
          </label>
        </div>
        <div class="border-t border-slate-200 px-3 py-2 text-right text-xs text-slate-500 dark:border-slate-700">
          <button type="button" class="rounded px-2 py-1 hover:bg-slate-100 dark:hover:bg-slate-700/50" @click="clear">Reset</button>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  options: { type: Array, default: () => [] },
  placeholder: { type: String, default: 'Pilih opsi' },
  maxTagCount: { type: Number, default: 2 },
});

const emit = defineEmits(['update:modelValue', 'change']);

const root = ref(null);
const open = ref(false);
const internal = ref(new Set(props.modelValue));

watch(
  () => props.modelValue,
  value => {
    internal.value = new Set(value ?? []);
  }
);

const selectedLabels = computed(() => {
  const selected = Array.from(internal.value);
  if (!selected.length) {
    return [];
  }

  return props.options
    .filter(option => selected.includes(option.value))
    .map(option => option.label);
});

const summary = computed(() => {
  if (selectedLabels.value.length <= props.maxTagCount) {
    return selectedLabels.value.join(', ');
  }

  const first = selectedLabels.value.slice(0, props.maxTagCount).join(', ');
  const rest = selectedLabels.value.length - props.maxTagCount;
  return `${first} +${rest}`;
});

function toggleOption(value) {
  const next = new Set(internal.value);
  if (next.has(value)) {
    next.delete(value);
  } else {
    next.add(value);
  }
  internal.value = next;
  const payload = Array.from(next);
  emit('update:modelValue', payload);
  emit('change', payload);
}

function clear() {
  internal.value = new Set();
  emit('update:modelValue', []);
  emit('change', []);
}

function toggle() {
  open.value = !open.value;
}

function close() {
  open.value = false;
}

function handleClickOutside(event) {
  if (!root.value) return;
  if (!open.value) return;
  if (root.value.contains(event.target)) return;
  close();
}

function handleKeyUp(event) {
  if (event.key === 'Escape') {
    close();
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside, true);
  document.addEventListener('keyup', handleKeyUp, true);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside, true);
  document.removeEventListener('keyup', handleKeyUp, true);
});
</script>

<style scoped>
.multiselect-trigger {
  @apply flex w-full items-center justify-between gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm transition hover:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-400/60 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200;
}

.multiselect-panel {
  @apply absolute z-50 mt-2 w-full min-w-[12rem] rounded-xl border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-800;
}

.dropdown-fade-enter-active,
.dropdown-fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.dropdown-fade-enter-from,
.dropdown-fade-leave-to {
  opacity: 0;
  transform: scale(0.98);
}

.dropdown-fade-enter-to,
.dropdown-fade-leave-from {
  opacity: 1;
  transform: scale(1);
}
</style>
