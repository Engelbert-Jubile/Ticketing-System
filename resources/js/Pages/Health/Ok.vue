<template>
  <div class="min-h-screen flex items-center justify-center bg-slate-100 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
    <div class="rounded-lg border border-slate-200 bg-white px-8 py-6 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
      <h1 class="text-2xl font-semibold tracking-tight">Inertia Health Check</h1>
      <p class="mt-3 text-base text-slate-600 dark:text-slate-300">{{ msg }}</p>
    </div>
  </div>
</template>

<script setup>
defineProps({
  msg: {
    type: String,
    default: 'Inertia alive',
  },
});
</script>
