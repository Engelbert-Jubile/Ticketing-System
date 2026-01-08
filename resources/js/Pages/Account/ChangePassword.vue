<template>
  <div class="mx-auto max-w-xl space-y-6 px-4 py-6">
    <header class="space-y-1">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Ubah Password</h1>
      <p class="text-sm text-slate-500 dark:text-slate-300">Pastikan memilih password yang kuat dan mudah diingat.</p>
    </header>

    <section v-if="flash.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
      {{ flash.success }}
    </section>
    <section v-else-if="flash.error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200">
      {{ flash.error }}
    </section>
    <section v-else-if="Object.keys(form.errors).length" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200">
      Periksa kembali input Anda.
    </section>

    <form class="space-y-5 rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-xl shadow-blue-100/40 ring-1 ring-slate-200/80 backdrop-blur dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-none dark:ring-slate-700" @submit.prevent="submit" novalidate>
      <div class="space-y-1">
        <label for="current_password" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Password Saat Ini</label>
        <div class="relative">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="currentColor" stroke-width="1.8" />
              <path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
          </span>
          <input
            id="current_password"
            v-model="form.current_password"
            :type="show.current ? 'text' : 'password'"
            autocomplete="current-password"
            required
            class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 pr-12 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-600 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-slate-700 dark:text-slate-300"
            @click="toggle('current')"
            aria-label="Tampilkan atau sembunyikan password saat ini"
          >
            <span class="material-icons text-base" v-if="show.current">visibility_off</span>
            <span class="material-icons text-base" v-else>visibility</span>
          </button>
        </div>
        <p v-if="form.errors.current_password" class="mt-1 text-xs text-rose-500">{{ form.errors.current_password }}</p>
      </div>

      <div class="space-y-1">
        <label for="password" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Password Baru</label>
        <div class="relative">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 10V8a6 6 0 1 1 12 0v2" stroke="currentColor" stroke-width="1.8" />
              <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8" />
              <path d="M12 14v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
          </span>
          <input
            id="password"
            v-model="form.password"
            :type="show.new ? 'text' : 'password'"
            autocomplete="new-password"
            required
            minlength="8"
            class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 pr-12 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-600 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-slate-700 dark:text-slate-300"
            @click="toggle('new')"
            aria-label="Tampilkan atau sembunyikan password baru"
          >
            <span class="material-icons text-base" v-if="show.new">visibility_off</span>
            <span class="material-icons text-base" v-else>visibility</span>
          </button>
        </div>
        <p v-if="form.errors.password" class="mt-1 text-xs text-rose-500">{{ form.errors.password }}</p>
      </div>

      <div class="space-y-1">
        <label for="password_confirmation" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Konfirmasi Password Baru</label>
        <div class="relative">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M6 10V8a6 6 0 1 1 12 0v2" stroke="currentColor" stroke-width="1.8" />
              <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.8" />
              <path d="M12 14v2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
          </span>
          <input
            id="password_confirmation"
            v-model="form.password_confirmation"
            :type="show.confirm ? 'text' : 'password'"
            autocomplete="new-password"
            required
            class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 pr-12 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-600 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none"
          />
          <button
            type="button"
            class="absolute inset-y-0 right-3 flex items-center text-slate-500 hover:text-slate-700 dark:text-slate-300"
            @click="toggle('confirm')"
            aria-label="Tampilkan atau sembunyikan konfirmasi password"
          >
            <span class="material-icons text-base" v-if="show.confirm">visibility_off</span>
            <span class="material-icons text-base" v-else>visibility</span>
          </button>
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <Link :href="route('dashboard')" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">
          Batal
        </Link>
        <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60" :disabled="form.processing">
          <span class="material-icons text-base">lock</span>
          Update Password
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, reactive } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

usePage();

const form = useForm({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const show = reactive({
  current: false,
  new: false,
  confirm: false,
});

function toggle(key) {
  show[key] = !show[key];
}

function submit() {
  form.put(route('account.password.update'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('current_password', 'password', 'password_confirmation');
    },
  });
}

const page = usePage();
const flash = computed(() => page.props.flash || {});
</script>

<style scoped>
.material-icons {
  font-size: inherit;
}
</style>
