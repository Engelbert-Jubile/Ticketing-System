<template>
  <div class="mx-auto max-w-2xl space-y-6 px-4 py-6">
    <header class="space-y-1">
      <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Profil</h1>
      <p class="text-sm text-slate-500 dark:text-slate-300">Perbarui informasi dasar akun Anda.</p>
    </header>

    <section v-if="flash.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
      {{ flash.success }}
    </section>
    <section v-else-if="Object.keys(form.errors).length" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200">
      Periksa kembali input Anda.
    </section>

    <form class="space-y-5 rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-xl shadow-blue-100/40 ring-1 ring-slate-200/80 backdrop-blur dark:border-slate-700 dark:bg-slate-900/80 dark:shadow-none dark:ring-slate-700" @submit.prevent="submit" novalidate>
      <div class="space-y-1">
        <label for="username" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Username</label>
        <div class="relative">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M12 12a4 4 0 1 0-4-4 4 4 0 0 0 4 4Z" stroke="currentColor" stroke-width="1.8" />
              <path d="M4 20a8 8 0 0 1 16 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            </svg>
          </span>
          <input
            id="username"
            v-model="form.username"
            type="text"
            autocomplete="username"
            required
            class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none"
          />
        </div>
        <p v-if="form.errors.username" class="text-xs text-rose-500">{{ form.errors.username }}</p>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-1">
          <label for="first_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Nama Depan</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6.5 19.5h11A2.5 2.5 0 0 0 20 17V7A2.5 2.5 0 0 0 17.5 4.5h-11A2.5 2.5 0 0 0 4 7v10A2.5 2.5 0 0 0 6.5 19.5Z" stroke="currentColor" stroke-width="1.8" />
                <path d="M8 9h8M8 12h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              </svg>
            </span>
            <input
              id="first_name"
              v-model="form.first_name"
              type="text"
              autocomplete="given-name"
              required
              class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none"
            />
          </div>
          <p v-if="form.errors.first_name" class="text-xs text-rose-500">{{ form.errors.first_name }}</p>
        </div>

        <div class="space-y-1">
          <label for="last_name" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Nama Belakang</label>
          <div class="relative">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M6.5 19.5h11A2.5 2.5 0 0 0 20 17V7A2.5 2.5 0 0 0 17.5 4.5h-11A2.5 2.5 0 0 0 4 7v10A2.5 2.5 0 0 0 6.5 19.5Z" stroke="currentColor" stroke-width="1.8" />
                <path d="M8 9h8M8 12h8M8 15h5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
              </svg>
            </span>
            <input
              id="last_name"
              v-model="form.last_name"
              type="text"
              autocomplete="family-name"
              class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none"
            />
          </div>
          <p v-if="form.errors.last_name" class="text-xs text-rose-500">{{ form.errors.last_name }}</p>
        </div>
      </div>

      <div class="space-y-1">
        <label for="email" class="block text-sm font-semibold text-slate-700 dark:text-slate-200">Email</label>
        <div class="relative">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M4 7.5A2.5 2.5 0 0 1 6.5 5h11A2.5 2.5 0 0 1 20 7.5v9A2.5 2.5 0 0 1 17.5 19h-11A2.5 2.5 0 0 1 4 16.5v-9Z" stroke="currentColor" stroke-width="1.8" />
              <path d="m5.5 7 6.1 4.07a1 1 0 0 0 1.1 0L18.8 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </span>
          <input
            id="email"
            v-model="form.email"
            type="email"
            autocomplete="email"
            required
            class="w-full rounded-xl border border-slate-200 bg-white/80 px-3 py-3 pl-10 pr-28 text-sm text-slate-900 shadow-sm shadow-slate-200/40 outline-none transition focus:border-blue-400 focus:ring-2 focus:ring-blue-500/30 dark:border-slate-700 dark:bg-slate-950/40 dark:text-slate-100 dark:shadow-none"
          />
          <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-xs font-semibold tracking-wide text-slate-500 dark:text-slate-300">{{ emailDomain }}</span>
        </div>
        <p v-if="form.errors.email" class="text-xs text-rose-500">{{ form.errors.email }}</p>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <Link :href="route('dashboard')" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">
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
import { computed, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
  user: { type: Object, required: true },
  meta: { type: Object, default: () => ({}) },
});

const defaultValues = {
  username: props.user.username || '',
  first_name: props.user.first_name || '',
  last_name: props.user.last_name || '',
  email: props.user.email || '',
};

const form = useForm({ ...defaultValues });

function submit() {
  const url = formAction.value;
  const options = { preserveScroll: true };

  switch (formMethod.value) {
    case 'patch':
      form.patch(url, options);
      break;
    case 'post':
      form.post(url, options);
      break;
    case 'put':
    default:
      form.put(url, options);
      break;
  }
}

const page = usePage();
const flash = computed(() => page.props.flash || {});

const formAction = computed(() => props.meta?.updateRoute ?? route('account.update-profile'));
const formMethod = computed(() => String(props.meta?.method ?? 'put').toLowerCase());

const emailDomain = computed(() => {
  const email = form.email || '';
  const at = email.indexOf('@');
  return at !== -1 ? email.slice(at) : '@kftd.co.id';
});

watch(
  () => props.user,
  value => {
    form.defaults({
      username: value?.username || '',
      first_name: value?.first_name || '',
      last_name: value?.last_name || '',
      email: value?.email || '',
    });
    form.reset();
  }
);
</script>

<style scoped>
.material-icons {
  font-size: inherit;
}
</style>
