<template>
  <EditForm :project="project" :options="options" :meta="meta" />
</template>

<script setup>
import EditForm from '@/Pages/Projects/Edit.vue';

defineProps({
  project: { type: Object, required: true },
  options: { type: Object, required: true },
  meta: { type: Object, required: true },
});
</script>
