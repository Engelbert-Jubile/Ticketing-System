<template>
  <div class="relative min-h-screen overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-slate-950">
    <div class="pointer-events-none absolute inset-0 opacity-80">
      <div class="absolute -left-24 top-16 h-80 w-80 rounded-full bg-blue-600/35 blur-3xl" />
      <div class="absolute -right-24 bottom-10 h-[26rem] w-[26rem] rounded-full bg-purple-500/25 blur-[140px]" />
      <div class="absolute left-1/2 top-1/2 h-[30rem] w-[30rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-cyan-500/15 blur-[180px]" />
    </div>

    <div class="relative flex min-h-screen items-center justify-center px-5 py-14 sm:px-8 lg:px-12">
      <div v-if="!singleColumn" class="grid w-full max-w-5xl items-center gap-12 lg:grid-cols-[minmax(0,1.1fr)_minmax(320px,1fr)] lg:gap-16">
        <div class="flex flex-col items-center text-center text-slate-100 lg:items-start lg:text-left">
          <Link :href="homeUrl" class="inline-flex items-center gap-4 rounded-full bg-white/10 px-6 py-3 text-sm font-semibold uppercase tracking-[0.35em] text-white ring-1 ring-white/20 backdrop-blur">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 text-xl shadow-[0_12px_40px_-20px_rgba(59,130,246,0.8)]">
              <span class="material-icons text-base">confirmation_number</span>
            </span>
            Ticketing System
          </Link>
          <h1 class="mt-10 text-4xl font-semibold leading-tight text-white sm:text-[44px] sm:leading-tight">
            Kelola tiket, task, dan project dengan tampilan modern
          </h1>
          <p class="mt-5 max-w-xl text-base text-slate-200">
            Platform terpadu untuk tim Anda. Login atau daftar untuk mulai memantau seluruh pekerjaan dengan lebih teratur dan menyenangkan.
          </p>
          <div class="mt-8 flex flex-wrap items-center justify-center gap-4 text-xs font-semibold uppercase tracking-[0.35em] text-slate-100 lg:justify-start">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-5 py-3 ring-1 ring-white/15">
              <span class="material-icons text-sm text-amber-300">workspace_premium</span>
              Pro Workspace
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-5 py-3 ring-1 ring-white/15">
              <span class="material-icons text-sm text-emerald-300">groups</span>
              Dipercaya tim produktif
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-5 py-3 ring-1 ring-white/15">
              <span class="material-icons text-sm text-sky-300">bolt</span>
              Integrasi cepat
            </span>
          </div>
        </div>

        <div class="flex items-center justify-center">
          <div class="w-full max-w-md">
            <slot />
          </div>
        </div>
      </div>

      <div v-else class="w-full max-w-4xl mx-auto grid gap-10 lg:gap-12">
        <div class="flex flex-col items-center text-center text-slate-100">
          <Link :href="homeUrl" class="inline-flex items-center gap-4 rounded-full bg-white/10 px-6 py-3 text-sm font-semibold uppercase tracking-[0.35em] text-white ring-1 ring-white/20 backdrop-blur">
            <span class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 via-indigo-500 to-purple-500 text-xl shadow-[0_12px_40px_-20px_rgba(59,130,246,0.8)]">
              <span class="material-icons text-base">confirmation_number</span>
            </span>
            Ticketing System
          </Link>
          <h1 class="mt-10 text-4xl font-semibold leading-tight text-white sm:text-[44px] sm:leading-tight">
            Kelola tiket, task, dan project dengan tampilan modern
          </h1>
          <p class="mt-5 max-w-xl text-base text-slate-200">
            Platform terpadu untuk tim Anda. Login atau daftar untuk mulai memantau seluruh pekerjaan dengan lebih teratur dan menyenangkan.
          </p>
          <div class="mt-8 flex flex-wrap items-center justify-center gap-4 text-xs font-semibold uppercase tracking-[0.35em] text-slate-100">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-5 py-3 ring-1 ring-white/15">
              <span class="material-icons text-sm text-amber-300">workspace_premium</span>
              Pro Workspace
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-5 py-3 ring-1 ring-white/15">
              <span class="material-icons text-sm text-emerald-300">groups</span>
              Dipercaya tim produktif
            </span>
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-5 py-3 ring-1 ring-white/15">
              <span class="material-icons text-sm text-sky-300">bolt</span>
              Integrasi cepat
            </span>
          </div>
        </div>

        <div class="flex items-center justify-center">
          <div class="w-full max-w-md">
            <slot />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import resolveRoute from '../utils/resolveRoute';

const props = defineProps({
  singleColumn: { type: Boolean, default: false },
});

const homeUrl = computed(() => resolveRoute('home'));
</script>
