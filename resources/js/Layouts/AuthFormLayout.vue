<template>
  <div class="public-page relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-100 px-4 py-12 dark:bg-slate-950">
    <div class="public-ambient pointer-events-none absolute inset-0 opacity-80">
      <div class="absolute -top-32 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-indigo-300/35 blur-[180px]" />
      <div class="absolute -bottom-24 right-10 h-[22rem] w-[22rem] rounded-full bg-cyan-300/25 blur-[190px]" />
      <div class="absolute -bottom-32 left-10 h-[20rem] w-[20rem] rounded-full bg-purple-400/20 blur-[180px]" />
    </div>

    <div class="relative z-10 w-full max-w-md space-y-8 text-center">
      <Link :href="homeUrl" class="inline-block w-full max-w-sm rounded-2xl bg-white/95 p-4 text-slate-700 shadow-sm">
            <BrandLogo tagline />
          </Link>

      <div class="public-content relative">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import BrandLogo from '../Components/BrandLogo.vue'
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import resolveRoute from '../utils/resolveRoute'

const homeUrl = computed(() => resolveRoute('home'))
</script>
