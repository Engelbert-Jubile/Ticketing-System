<template>
  <div class="date-picker-flatpickr">
    <input
      ref="input"
      type="text"
      :placeholder="placeholder"
      :disabled="disabled"
      class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-500 dark:focus:ring-blue-500/40"
    />
  </div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Date, null], default: null },
  placeholder: { type: String, default: 'Pilih tanggal' },
  disabled: { type: Boolean, default: false },
  config: {
    type: Object,
    default: () => ({ enableTime: false, dateFormat: 'Y-m-d' }),
  },
});

const emit = defineEmits(['update:modelValue', 'change']);

const input = ref(null);
let instance = null;

const applyDisabled = disabled => {
  if (input.value) {
    input.value.disabled = Boolean(disabled);
  }
  if (!instance) return;
  try {
    instance.set('clickOpens', !disabled);
  } catch (error) {}
  try {
    if (instance.input) instance.input.disabled = Boolean(disabled);
    if (instance.altInput) instance.altInput.disabled = Boolean(disabled);
  } catch (error) {}
};

const setup = async () => {
  const [{ default: flatpickr }] = await Promise.all([
    import('flatpickr'),
    import('flatpickr/dist/flatpickr.css'),
  ]);

  instance = flatpickr(input.value, {
    ...props.config,
    defaultDate: props.modelValue ?? undefined,
    onChange(selectedDates, dateStr) {
      emit('update:modelValue', dateStr);
      emit('change', { selectedDates, dateStr });
    },
  });

  applyDisabled(props.disabled);
};

onMounted(setup);

watch(
  () => props.modelValue,
  value => {
    if (!instance) return;
    const current = instance.input.value;
    const next = value ?? '';
    if (current !== next) {
      instance.setDate(next, false);
    }
  }
);

watch(
  () => props.disabled,
  disabled => {
    applyDisabled(disabled);
  }
);

watch(
  () => props.config,
  () => {
    if (!instance) return;
    const currentValue = instance.input.value;
    instance.destroy();
    instance = null;
    setup().then(() => {
      if (currentValue) {
        instance.setDate(currentValue, false);
      }
    });
  },
  { deep: true }
);

onBeforeUnmount(() => {
  if (instance) {
    instance.destroy();
    instance = null;
  }
});
</script>

<style scoped>
.date-picker-flatpickr {
  width: 100%;
}

:global(.flatpickr-calendar) {
  z-index: 35 !important;
}
</style>
