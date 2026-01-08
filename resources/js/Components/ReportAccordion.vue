<template>
  <section class="report-accordion" :data-theme="theme">
    <button class="report-accordion__header" type="button" @click="toggle" :aria-expanded="open">
      <div class="report-accordion__header-content">
        <div v-if="icon" class="report-accordion__icon" aria-hidden="true">
          <span class="material-icons">{{ icon }}</span>
        </div>
        <div class="report-accordion__text">
          <slot name="title">
            <h2 class="report-accordion__title">{{ title }}</h2>
          </slot>
          <slot name="subtitle">
            <p v-if="subtitle" class="report-accordion__subtitle">{{ subtitle }}</p>
          </slot>
        </div>
      </div>
      <div class="report-accordion__meta">
        <slot name="meta">
          <div class="report-accordion__counts" v-if="counts">
            <span class="report-accordion__count" v-if="counts.total !== undefined">Total: {{ counts.total }}</span>
            <span class="report-accordion__count" v-if="counts.in_progress !== undefined">In Progress: {{ counts.in_progress }}</span>
            <span class="report-accordion__count" v-if="counts.done !== undefined">Done: {{ counts.done }}</span>
          </div>
        </slot>
        <span class="report-accordion__chevron" :class="{ 'report-accordion__chevron--open': open }" aria-hidden="true">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 9l6 6 6-6"/></svg>
        </span>
      </div>
    </button>

    <Transition name="accordion">
      <div v-show="open" class="report-accordion__body">
        <slot :open="open" />
      </div>
    </Transition>
  </section>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  icon: { type: String, default: '' },
  counts: { type: Object, default: null },
  modelValue: { type: Boolean, default: false },
  theme: { type: String, default: 'default' },
});

const emit = defineEmits(['update:modelValue', 'toggle']);

const open = ref(props.modelValue);

watch(
  () => props.modelValue,
  value => {
    if (value !== open.value) {
      open.value = value;
    }
  }
);

function toggle() {
  open.value = !open.value;
  emit('update:modelValue', open.value);
  emit('toggle', open.value);
}
</script>

<style scoped>
.report-accordion {
  border-radius: 1.25rem;
  border: 1px solid rgba(148, 163, 184, 0.35);
  background: rgba(255, 255, 255, 0.95);
  overflow: hidden;
}

.report-accordion__header {
  width: 100%;
  padding: 1.2rem 1.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  background: transparent;
  border: none;
  cursor: pointer;
  text-align: left;
}

.report-accordion__header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.report-accordion__icon {
  font-size: 2rem;
  line-height: 1;
}

.report-accordion__icon .material-icons {
  font-size: 2rem;
  line-height: 1;
}

.report-accordion__title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #0f172a;
}

.report-accordion__subtitle {
  margin-top: 0.25rem;
  color: #475569;
  font-size: 0.85rem;
}

.report-accordion__meta {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.report-accordion__counts {
  display: flex;
  gap: 0.75rem;
  font-size: 0.8rem;
  color: #334155;
}

.report-accordion__chevron {
  display: inline-flex;
  width: 1.5rem;
  height: 1.5rem;
  align-items: center;
  justify-content: center;
  color: #1e293b;
  transition: transform 0.2s ease;
}

.report-accordion__chevron--open {
  transform: rotate(180deg);
}

.report-accordion__body {
  padding: 0 1.5rem 1.5rem;
}

.accordion-enter-active,
.accordion-leave-active {
  overflow: hidden;
  transition: height 0.22s ease;
}

.accordion-enter-from,
.accordion-leave-to {
  height: 0;
}

.accordion-enter-to,
.accordion-leave-from {
  height: auto;
}

.dark .report-accordion {
  border-color: rgba(51, 65, 85, 0.55);
  background: rgba(15, 23, 42, 0.88);
}

.dark .report-accordion__title { color: #e2e8f0; }
.dark .report-accordion__subtitle { color: #cbd5f5; }
.dark .report-accordion__counts { color: #cbd5f5; }
.dark .report-accordion__chevron { color: #cbd5f5; }

.report-accordion[data-theme='blue'] .report-accordion__title { color: #1d4ed8; }
.report-accordion[data-theme='green'] .report-accordion__title { color: #047857; }

.report-accordion[data-theme='blue'] .report-accordion__subtitle { color: #1e3a8a; }
.report-accordion[data-theme='green'] .report-accordion__subtitle { color: #065f46; }

.dark .report-accordion[data-theme='blue'] .report-accordion__title { color: #93c5fd; }
.dark .report-accordion[data-theme='green'] .report-accordion__title { color: #6ee7b7; }
</style>
