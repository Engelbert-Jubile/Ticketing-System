<template>
  <section class="space-y-12">
    <Head title="Ticketing System" />

    <div class="space-y-4 text-center">
      <span class="inline-flex items-center justify-center gap-2 rounded-full border border-indigo-200/70 bg-white/70 px-5 py-2 text-xs font-semibold uppercase tracking-[0.35em] text-indigo-500">
        <span class="material-icons text-base text-indigo-500">confirmation_number</span>
        Ticketing System
      </span>
      <h1 class="text-3xl font-bold text-slate-900">Kelola tiket, task, dan project secara terpadu</h1>
      <p class="text-sm text-gray-500">
        Pantau pekerjaan tim Anda dengan antarmuka modern yang konsisten di seluruh perangkat. Mulai dengan login atau daftar akun baru dalam hitungan detik.
      </p>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-3">
      <div
        v-for="chip in chips"
        :key="chip.label"
        class="flex items-center gap-2 rounded-full border border-slate-200/80 bg-white/80 px-4 py-2 text-xs font-semibold text-slate-600 shadow-sm"
      >
        <span class="material-icons text-base" :class="chip.iconColor">{{ chip.icon }}</span>
        <span class="tracking-wide">{{ chip.label }}</span>
      </div>
    </div>

    <div class="mx-auto rounded-2xl bg-white/95 p-6 shadow-lg shadow-indigo-200/40 ring-1 ring-indigo-100 sm:p-8">
      <div class="space-y-3 text-center">
        <h2 class="text-3xl font-bold text-slate-900">Mulai Atur Workspace</h2>
        <p class="text-sm text-gray-500">
          Satu klik untuk kembali ke pekerjaan Anda atau buat akun baru bagi anggota tim yang belum terdaftar.
        </p>
      </div>

      <div class="mt-8 flex flex-col gap-4 sm:flex-row">
        <Link
          :href="loginUrl"
          class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-indigo-500 to-cyan-400 text-sm font-semibold text-white shadow-lg shadow-indigo-400/40 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-200 focus-visible:ring-offset-2 focus-visible:ring-offset-white hover:from-indigo-600 hover:to-cyan-500 active:from-indigo-700 active:to-cyan-600"
        >
          <span class="material-icons text-base">login</span>
          <span>Login</span>
        </Link>

        <Link
          :href="registerUrl"
          class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-400 text-sm font-semibold text-white shadow-lg shadow-emerald-400/40 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200 focus-visible:ring-offset-2 focus-visible:ring-offset-white hover:from-emerald-600 hover:to-teal-500 active:from-emerald-700 active:to-teal-600"
        >
          <span class="material-icons text-base">person_add</span>
          <span>Register</span>
        </Link>
      </div>
    </div>
  </section>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3'
import { computed } from 'vue'
import resolveRoute from '../utils/resolveRoute'

const loginUrl = computed(() => resolveRoute('login'))
const registerUrl = computed(() => resolveRoute('register'))

const chips = [
  { icon: 'workspace_premium', iconColor: 'text-indigo-500', label: 'Pro Workspace' },
  { icon: 'groups', iconColor: 'text-emerald-500', label: 'Dipercaya tim produktif' },
  { icon: 'bolt', iconColor: 'text-sky-500', label: 'Integrasi cepat' },
]
</script>
