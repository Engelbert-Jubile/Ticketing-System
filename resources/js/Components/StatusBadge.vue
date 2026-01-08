<template>
  <StatusPill v-bind="props">
    <slot />
  </StatusPill>
</template>

<script setup>
import StatusPill from './StatusPill.vue';

const props = defineProps({
  status: { type: [String, Number], default: '' },
  label: { type: String, default: '' },
  variant: { type: String, default: 'status' },
  size: { type: String, default: 'md' },
});
</script>
