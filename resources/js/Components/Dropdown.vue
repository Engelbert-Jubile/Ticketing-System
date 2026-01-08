<template>
  <div class="relative inline-flex" ref="root">
    <button
      type="button"
      :class="['dropdown-trigger', triggerClass]"
      @click.stop="toggle"
      @keydown.enter.prevent="toggle"
      @keydown.space.prevent="toggle"
      @keydown.escape.stop.prevent="close"
      :aria-expanded="open"
      aria-haspopup="menu"
    >
      <slot name="trigger" :open="open" />
    </button>

    <Transition name="dropdown-fade">
      <div
        v-if="open"
        class="dropdown-menu"
        :class="[widthClass, alignClass]"
        @click="handleItemClick"
        role="menu"
      >
        <slot :close="close" />
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  align: { type: String, default: 'right' },
  widthClass: { type: String, default: 'w-40' },
  triggerClass: { type: String, default: 'inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' },
});

const emit = defineEmits(['update:modelValue', 'close']);

const root = ref(null);
const open = ref(props.modelValue);

watch(
  () => props.modelValue,
  value => {
    if (value !== open.value) {
      open.value = value;
    }
  }
);

watch(open, value => {
  if (value !== props.modelValue) {
    emit('update:modelValue', value);
  }
  if (!value) {
    emit('close');
  }
});

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

function handleItemClick(event) {
  const target = event.target;
  if (target?.dataset?.dropdownClose === 'false') {
    return;
  }
  close();
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside, true);
  document.addEventListener('keyup', handleKeyUp, true);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside, true);
  document.removeEventListener('keyup', handleKeyUp, true);
});

function handleKeyUp(event) {
  if (event.key === 'Escape') {
    close();
  }
}

const alignClass = computed(() => {
  switch (props.align) {
    case 'left':
      return 'left-0 origin-top-left';
    case 'center':
      return 'left-1/2 -translate-x-1/2 origin-top';
    default:
      return 'right-0 origin-top-right';
  }
});
</script>

<style scoped>
.dropdown-menu {
  @apply absolute z-50 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 text-sm shadow-lg dark:border-slate-700 dark:bg-slate-800;
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
