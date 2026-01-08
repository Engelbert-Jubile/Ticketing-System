<template>
  <div class="mx-auto max-w-3xl space-y-6 px-4 py-6">
    <header class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Create User</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Tambahkan akun baru dan tetapkan peran yang sesuai.</p>
      </div>
      <Link :href="route('users.report')" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline dark:text-blue-300">
        <span class="material-icons text-base">arrow_back</span>
        Kembali
      </Link>
    </header>

    <section v-if="flash.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
      {{ flash.success }}
    </section>
    <section v-if="flash.error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200">
      {{ flash.error }}
    </section>

    <form class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900" @submit.prevent="submit">
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label for="username" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Username</label>
          <input
            id="username"
            v-model="form.username"
            type="text"
            autocomplete="username"
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            required
          />
          <p v-if="form.errors.username" class="mt-1 text-xs text-rose-500">{{ form.errors.username }}</p>
        </div>

        <div>
          <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Email</label>
          <input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            required
          />
          <p v-if="form.errors.email" class="mt-1 text-xs text-rose-500">{{ form.errors.email }}</p>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label for="first_name" class="block text-sm font-medium text-slate-700 dark:text-slate-200">First Name</label>
          <input
            id="first_name"
            v-model="form.first_name"
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            required
          />
          <p v-if="form.errors.first_name" class="mt-1 text-xs text-rose-500">{{ form.errors.first_name }}</p>
        </div>
        <div>
          <label for="last_name" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Last Name (opsional)</label>
          <input
            id="last_name"
            v-model="form.last_name"
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          />
          <p v-if="form.errors.last_name" class="mt-1 text-xs text-rose-500">{{ form.errors.last_name }}</p>
        </div>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Password</label>
          <input
            id="password"
            v-model="form.password"
            type="password"
            autocomplete="new-password"
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            required
            minlength="6"
          />
          <p v-if="form.errors.password" class="mt-1 text-xs text-rose-500">{{ form.errors.password }}</p>
        </div>
        <div>
          <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Konfirmasi Password</label>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            type="password"
            autocomplete="new-password"
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            required
          />
        </div>
      </div>

      <div>
        <label for="role" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Role</label>
        <select
          id="role"
          v-model="form.role"
          class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          required
        >
          <option value="" disabled>Pilih Role</option>
          <option v-for="option in roles" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
        <p v-if="form.errors.role" class="mt-1 text-xs text-rose-500">{{ form.errors.role }}</p>
      </div>

      <div>
        <label for="unit" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Unit</label>
        <select
          id="unit"
          v-model="form.unit"
          class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :required="unitRequired"
        >
          <option value="" disabled>Pilih Unit</option>
          <option v-for="unit in unitOptions" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
        </select>
        <p v-if="form.errors.unit" class="mt-1 text-xs text-rose-500">{{ form.errors.unit }}</p>
        <p v-else-if="unitRequired" class="mt-1 text-xs text-slate-500">Unit wajib diisi bila akun dibuat oleh Admin/User.</p>
      </div>

      <div class="flex justify-end gap-2">
        <Link :href="route('users.report')" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">
          Batal
        </Link>
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60" :disabled="form.processing">
          <span class="material-icons text-base">save</span>
          Simpan
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { useRoute } from 'ziggy-js';
import { Ziggy } from '@/ziggy';

const props = defineProps({
  roles: { type: Array, default: () => [] },
  units: { type: Array, default: () => [] },
  meta: { type: Object, default: () => ({}) },
});

const form = useForm({
  username: '',
  first_name: '',
  last_name: '',
  email: '',
  password: '',
  password_confirmation: '',
  role: '',
  unit: '',
});

const route = useRoute(Ziggy);

if (props.roles.length === 1) {
  form.role = props.roles[0].value;
}

function submit() {
  form.post(route('users.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('password', 'password_confirmation');
    },
  });
}

const page = usePage();
const flash = computed(() => page.props.flash || {});

const roles = computed(() => props.roles ?? []);
const unitOptions = computed(() => props.units ?? []);
const unitRequired = computed(() => props.meta?.unitRequired ?? true);
</script>

<style scoped>
.material-icons {
  font-size: inherit;
}
</style>
