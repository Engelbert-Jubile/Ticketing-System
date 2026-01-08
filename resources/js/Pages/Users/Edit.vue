<template>
  <div class="mx-auto max-w-3xl space-y-6 px-4 py-6">
    <header class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Edit User</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Perbarui informasi pengguna dan hak akses.</p>
      </div>
      <Link :href="backUrl" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline dark:text-blue-300">
        <span class="material-icons text-base">arrow_back</span>
        Kembali
      </Link>
    </header>

    <form class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900" @submit.prevent="submit">
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label for="username" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Username</label>
          <input
            id="username"
            v-model="form.username"
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
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
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
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
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
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
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          />
          <p v-if="form.errors.last_name" class="mt-1 text-xs text-rose-500">{{ form.errors.last_name }}</p>
        </div>
      </div>

      <div>
        <label for="role" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Role</label>
        <select
          id="role"
          v-model="form.role"
          class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          required
        >
          <option v-for="option in roles" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
        <p v-if="form.errors.role" class="mt-1 text-xs text-rose-500">{{ form.errors.role }}</p>
      </div>

      <div>
        <label for="unit" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Unit</label>
        <select
          id="unit"
          v-model="form.unit"
          class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          :required="unitRequired"
        >
          <option value="" disabled>Pilih Unit</option>
          <option v-for="unit in unitOptions" :key="unit.value" :value="unit.value">{{ unit.label }}</option>
        </select>
        <p v-if="form.errors.unit" class="mt-1 text-xs text-rose-500">{{ form.errors.unit }}</p>
        <p v-else-if="unitRequired" class="mt-1 text-xs text-slate-500">Unit wajib diisi bila akun dikelola Admin/User.</p>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Password (opsional)</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          placeholder="Isi jika ingin mengubah password"
          class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          minlength="6"
        />
        <p v-if="form.errors.password" class="mt-1 text-xs text-rose-500">{{ form.errors.password }}</p>
      </div>

      <div class="flex flex-wrap items-center justify-between gap-3 pt-3">
        <button
          v-if="can.delete"
          type="button"
          class="inline-flex items-center gap-2 rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/50 dark:text-rose-300 dark:hover:bg-rose-900/20"
          @click="confirmDelete"
        >
          <span class="material-icons text-base">delete</span>
          Hapus User
        </button>

        <div class="ml-auto flex gap-2">
          <Link :href="backUrl" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">
            Batal
          </Link>
          <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60" :disabled="form.processing">
            <span class="material-icons text-base">save</span>
            Simpan Perubahan
          </button>
        </div>
      </div>
    </form>

    <Teleport to="body">
      <Transition name="fade">
        <div v-if="deleteDialog.open" class="fixed inset-0 z-50 grid place-items-center bg-black/40 px-4">
          <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-700 dark:bg-slate-900">
            <div class="flex items-center gap-2">
              <span class="material-icons text-rose-500">delete</span>
              <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Hapus User</h3>
            </div>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
              Anda yakin ingin menghapus akun <strong>{{ user.username }}</strong>?
            </p>
            <div class="mt-6 flex justify-end gap-2">
              <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800" @click="closeDelete">
                Batal
              </button>
              <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700" :disabled="deleteDialog.processing" @click="performDelete">
                <span class="material-icons text-base">delete</span>
                Hapus
              </button>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { useRoute } from 'ziggy-js';
import { Ziggy } from '@/ziggy';

const props = defineProps({
  user: { type: Object, required: true },
  roles: { type: Array, default: () => [] },
  units: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
  meta: { type: Object, default: () => ({}) },
});

const route = useRoute(Ziggy);

const form = useForm({
  username: props.user.username || '',
  email: props.user.email || '',
  first_name: props.user.first_name || '',
  last_name: props.user.last_name || '',
  role: props.user.role || (props.roles[0]?.value ?? 'user'),
  password: '',
  unit: props.user.unit || '',
});

function submit() {
  form.put(route('users.update', props.user.id), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('password');
    },
  });
}

const deleteDialog = ref({ open: false, processing: false });

function confirmDelete() {
  deleteDialog.value.open = true;
}

function closeDelete() {
  deleteDialog.value.open = false;
}

function performDelete() {
  deleteDialog.value.processing = true;
  router.delete(route('users.destroy', props.user.id), {
    preserveScroll: true,
    onFinish: () => {
      deleteDialog.value.processing = false;
      deleteDialog.value.open = false;
    },
  });
}

function handleEscape(event) {
  if (event.key === 'Escape' && deleteDialog.value.open) {
    closeDelete();
  }
}

onMounted(() => document.addEventListener('keydown', handleEscape));
onBeforeUnmount(() => document.removeEventListener('keydown', handleEscape));

const roles = computed(() => props.roles ?? []);
const unitOptions = computed(() => props.units ?? []);
const unitRequired = computed(() => props.meta?.unitRequired ?? true);
const can = computed(() => props.can ?? {});
const backUrl = computed(() => props.meta?.from || route('users.report'));

const user = computed(() => props.user);
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.material-icons {
  font-size: inherit;
}
</style>
