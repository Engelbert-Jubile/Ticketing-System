<template>
  <span class="tickme-brand">
    <img :src="stacked ? branding.stacked_logo : branding.logo" :alt="branding.name" :width="stacked ? 1254 : 1600" :height="stacked ? 1254 : 420" />
    <span v-if="tagline" class="tickme-brand__tagline">{{ branding.tagline }}</span>
  </span>
</template>

<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

defineProps({ tagline: { type: Boolean, default: false }, stacked: { type: Boolean, default: false } })
const page = usePage()
const branding = computed(() => page.props.branding)
</script>

<style scoped>
.tickme-brand { display: inline-flex; flex-direction: column; align-items: center; width: 100%; min-width: 0; gap: 12px; }
.tickme-brand img { display: block; width: 100%; height: auto; border-radius: 10px; }
.tickme-brand__tagline { font-size: 14px; font-weight: 500; line-height: 1.6; letter-spacing: normal; text-transform: none; text-align: center; color: inherit; }
</style>
