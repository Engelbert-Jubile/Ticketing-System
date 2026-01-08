<template>
  <div class="relative flex min-h-screen items-center justify-center overflow-hidden bg-slate-100 px-4 py-12 dark:bg-slate-950">
    <div class="pointer-events-none absolute inset-0 opacity-80">
      <div class="absolute -top-32 left-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-indigo-300/35 blur-[180px]" />
      <div class="absolute -bottom-24 right-10 h-[22rem] w-[22rem] rounded-full bg-cyan-300/25 blur-[190px]" />
      <div class="absolute -bottom-32 left-10 h-[20rem] w-[20rem] rounded-full bg-purple-400/20 blur-[180px]" />
    </div>

    <div class="relative z-10 w-full max-w-md space-y-8 text-center">
      <Link :href="homeUrl" class="inline-flex items-center gap-3 rounded-full border border-white/60 bg-white/80 px-5 py-3 text-xs font-semibold uppercase tracking-[0.35em] text-slate-600 shadow-sm backdrop-blur dark:border-white/20 dark:bg-slate-900/70 dark:text-slate-200">
        <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-indigo-500 via-purple-500 to-cyan-400 text-white shadow-lg shadow-indigo-500/35">
          <span class="material-icons text-base">confirmation_number</span>
        </span>
        Ticketing System
      </Link>

      <div class="relative">
        <slot />
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import resolveRoute from '../utils/resolveRoute'

const homeUrl = computed(() => resolveRoute('home'))
</script>
