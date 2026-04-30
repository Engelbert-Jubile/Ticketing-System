<template>
  <div class="space-y-8">
    <Head title="Masuk" />

    <div class="space-y-2 text-center">
      <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">Masuk</h1>
      <p class="text-sm text-gray-500 dark:text-slate-400">Akses dashboard ticketing Anda dari desktop maupun mobile.</p>
    </div>

    <form @submit.prevent="submit" class="card space-y-5 rounded-2xl bg-white/95 p-6 shadow-lg shadow-indigo-200/35 ring-1 ring-indigo-100 dark:bg-slate-900/90 dark:ring-slate-700 sm:p-8" novalidate>
      <div class="space-y-2 text-left">
        <label for="email" class="text-sm font-semibold text-slate-600 dark:text-slate-300">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          autocomplete="username"
          class="auth-input-stable h-12 w-full rounded-lg border border-gray-200 bg-white px-4 text-sm text-slate-900 shadow-sm transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        />
        <p v-if="form.errors.email" class="text-sm text-rose-500">{{ form.errors.email }}</p>
      </div>

      <div class="space-y-2 text-left">
        <label for="password" class="text-sm font-semibold text-slate-600 dark:text-slate-300">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          required
          autocomplete="current-password"
          class="auth-input-stable h-12 w-full rounded-lg border border-gray-200 bg-white px-4 text-sm text-slate-900 shadow-sm transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100"
        />
        <p v-if="form.errors.password" class="text-sm text-rose-500">{{ form.errors.password }}</p>
      </div>

      <button
        type="submit"
        :disabled="form.processing"
        class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-indigo-500 to-blue-500 text-sm font-semibold text-white shadow-lg shadow-indigo-400/35 transition hover:from-indigo-600 hover:to-blue-600 disabled:cursor-not-allowed disabled:opacity-75"
      >
        <span class="material-icons text-base">{{ form.processing ? 'hourglass_top' : 'login' }}</span>
        <span>{{ form.processing ? 'Memproses...' : 'Masuk' }}</span>
      </button>
    </form>

    <p class="text-center text-sm text-gray-500 dark:text-slate-400">
      Belum punya akun?
      <Link :href="registerUrl" class="font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300 dark:hover:text-indigo-200">Daftar di sini</Link>
    </p>
  </div>
</template>

<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import AuthFormLayout from '../../Layouts/AuthFormLayout.vue'
import resolveRoute from '../../utils/resolveRoute'

defineOptions({ layout: AuthFormLayout })

const form = useForm({
  email: '',
  password: '',
})

const loginAction = computed(() => resolveRoute('login.store'))
const registerUrl = computed(() => resolveRoute('register'))

const submit = () => {
  form
    .transform(data => ({
      ...data,
      email: data.email.trim().toLowerCase(),
    }))
    .post(loginAction.value, {
      onFinish: () => form.reset('password'),
    })
}
</script>
