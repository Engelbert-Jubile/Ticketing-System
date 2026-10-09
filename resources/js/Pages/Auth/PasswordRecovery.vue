<template>
  <Head :title="token ? 'Password baru' : 'Pulihkan akun'" />
  <main class="mx-auto w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 text-slate-900 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100">
    <h1 class="text-2xl font-bold">{{ token ? 'Buat password baru' : 'Pulihkan akun' }}</h1>
    <p class="mt-2 text-sm text-slate-500">{{ token ? 'Tautan hanya dapat digunakan sekali.' : 'Masukkan email akun untuk menerima tautan pemulihan.' }}</p>
    <p v-if="page.props.flash?.success" role="status" class="mt-4 text-sm text-emerald-700">{{ page.props.flash.success }}</p>
    <form @submit.prevent="submit" class="mt-5 space-y-4">
      <label class="block text-sm">Email<input v-model="form.email" required type="email" autocomplete="email" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label>
      <template v-if="token"><label class="block text-sm">Password baru<input v-model="form.password" required type="password" autocomplete="new-password" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label><label class="block text-sm">Ulangi password<input v-model="form.password_confirmation" required type="password" autocomplete="new-password" class="mt-1 w-full rounded-lg dark:bg-slate-800" /></label></template>
      <p v-for="(message, field) in form.errors" :key="field" role="alert" class="text-sm text-rose-600">{{ message }}</p>
      <button :disabled="form.processing" class="w-full rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white disabled:opacity-50">{{ form.processing ? 'Memproses…' : token ? 'Simpan password' : 'Kirim tautan' }}</button>
    </form>
    <a :href="resolveRoute('login')" class="mt-5 inline-block text-sm text-blue-600">Kembali ke login</a>
  </main>
</template>
<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3'
import MinimalAuthLayout from '@/Layouts/MinimalAuthLayout.vue'
import resolveRoute from '@/utils/resolveRoute'
defineOptions({ layout: MinimalAuthLayout })
const props = defineProps({ token: String, email: String })
const page = usePage()
const form = useForm({ email: props.email || '', token: props.token, password: '', password_confirmation: '' })
const submit = () => form.post(resolveRoute(props.token ? 'password.update' : 'password.email'), { onSuccess: () => form.reset('password', 'password_confirmation') })
</script>
