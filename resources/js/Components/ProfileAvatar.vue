<template>
  <span class="inline-flex shrink-0 overflow-hidden rounded-full bg-indigo-100" role="img" :aria-label="label">
    <img v-if="src && !failed" :src="src" :alt="label" class="h-full w-full object-cover" @error="failed = true" />
    <svg v-else viewBox="0 0 120 120" class="h-full w-full" aria-hidden="true">
      <rect width="120" height="120" fill="#e0e7ff" />
      <path v-if="template === 'female'" d="M27 91V53c0-48 66-48 66 0v38" fill="#334155" />
      <path d="M13 120c0-42 94-42 94 0" fill="#6366f1" /><path d="M49 77h22v20c-7 8-15 8-22 0" fill="#e8b18e" />
      <ellipse cx="60" cy="56" rx="25" ry="31" fill="#f5c9a8" />
      <path v-if="template === 'female'" d="M33 55c-6-45 61-47 55 0-15-3-27-14-31-23-2 14-12 21-24 23" fill="#334155" />
      <path v-else d="M34 52c-13-39 59-49 53 0L75 35c-12 10-23 4-32 9l-7 14" fill="#334155" />
      <circle cx="50" cy="58" r="2" fill="#334155" /><circle cx="70" cy="58" r="2" fill="#334155" />
      <path d="M53 72q7 6 14 0" fill="none" stroke="#b96e62" stroke-width="2.5" stroke-linecap="round" />
    </svg>
  </span>
</template>
<script setup>
import { ref, watch } from 'vue'
const props = defineProps({ src: String, template: { type: String, default: 'male' }, label: { type: String, default: 'Avatar' } })
const failed = ref(false)
watch(() => props.src, () => { failed.value = false })
</script>
