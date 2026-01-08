<template>
  <div class="space-y-8">
    <Head title="Daftar" />

    <div class="space-y-2 text-center">
      <h1 class="text-3xl font-bold text-slate-900">Buat Akun</h1>
      <p class="text-sm text-gray-500">Lengkapi data berikut untuk mulai menggunakan Ticketing System.</p>
    </div>

    <form @submit.prevent="submit" class="card grid gap-6 rounded-2xl bg-white/95 p-6 shadow-lg shadow-emerald-200/40 ring-1 ring-emerald-100 sm:grid-cols-2 sm:p-8">
      <div class="space-y-2">
        <label for="username" class="text-sm font-semibold text-slate-600">Username</label>
        <input
          id="username"
          v-model="form.username"
          type="text"
          maxlength="10"
          required
          class="input h-12 w-full rounded-lg border border-gray-200 bg-white px-4 text-sm text-slate-900 shadow-sm transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        />
        <p v-if="form.errors.username" class="input-error text-sm text-rose-500">{{ form.errors.username }}</p>
      </div>

      <div class="space-y-2">
        <label for="email" class="text-sm font-semibold text-slate-600">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          required
          class="input h-12 w-full rounded-lg border border-gray-200 bg-white px-4 text-sm text-slate-900 shadow-sm transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        />
        <p v-if="form.errors.email" class="input-error text-sm text-rose-500">{{ form.errors.email }}</p>
      </div>

      <div class="space-y-2">
        <label for="first_name" class="text-sm font-semibold text-slate-600">Nama Depan</label>
        <input
          id="first_name"
          v-model="form.first_name"
          type="text"
          required
          class="input h-12 w-full rounded-lg border border-gray-200 bg-white px-4 text-sm text-slate-900 shadow-sm transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        />
        <p v-if="form.errors.first_name" class="input-error text-sm text-rose-500">{{ form.errors.first_name }}</p>
      </div>

      <div class="space-y-2">
        <label for="last_name" class="text-sm font-semibold text-slate-600">Nama Belakang (opsional)</label>
        <input
          id="last_name"
          v-model="form.last_name"
          type="text"
          class="input h-12 w-full rounded-lg border border-gray-200 bg-white px-4 text-sm text-slate-900 shadow-sm transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        />
        <p v-if="form.errors.last_name" class="input-error text-sm text-rose-500">{{ form.errors.last_name }}</p>
      </div>

      <div class="space-y-2">
        <label for="password" class="text-sm font-semibold text-slate-600">Password</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          minlength="8"
          required
          autocomplete="new-password"
          class="input h-12 w-full rounded-lg border border-gray-200 bg-white px-4 text-sm text-slate-900 shadow-sm transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        />
        <p v-if="form.errors.password" class="input-error text-sm text-rose-500">{{ form.errors.password }}</p>
      </div>

      <div class="space-y-2">
        <label for="password_confirmation" class="text-sm font-semibold text-slate-600">Konfirmasi Password</label>
        <input
          id="password_confirmation"
          v-model="form.password_confirmation"
          type="password"
          minlength="8"
          required
          autocomplete="new-password"
          class="input h-12 w-full rounded-lg border border-gray-200 bg-white px-4 text-sm text-slate-900 shadow-sm transition focus:border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        />
      </div>

      <div class="sm:col-span-2">
        <button
          type="submit"
          :disabled="form.processing"
          class="btn-primary inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-emerald-500 to-teal-400 text-sm font-semibold text-white shadow-lg shadow-emerald-400/40 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-200 focus-visible:ring-offset-2 focus-visible:ring-offset-white hover:from-emerald-600 hover:to-teal-500 active:from-emerald-700 active:to-teal-600 disabled:cursor-not-allowed disabled:opacity-75"
        >
          <span class="material-icons text-base">person_add</span>
          <span>Buat Akun</span>
        </button>
      </div>
    </form>

    <p class="text-center text-sm text-gray-500">
      Sudah punya akun?
      <Link :href="loginUrl" class="font-semibold text-indigo-600 hover:text-indigo-700">Masuk di sini</Link>
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
  username: '',
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const registerAction = computed(() => resolveRoute('register.store'))
const loginUrl = computed(() => resolveRoute('login'))

const submit = () => {
  form
    .transform(data => ({
      ...data,
      email: data.email.trim().toLowerCase(),
    }))
    .post(registerAction.value, {
      onSuccess: () => form.reset('password', 'password_confirmation'),
      onError: () => form.reset('password', 'password_confirmation'),
    })
}
</script>
