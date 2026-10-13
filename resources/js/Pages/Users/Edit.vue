<template>
  <div class="mx-auto max-w-3xl space-y-6 px-4 py-6">
    <header class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">Edit User</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">{{ $t("Perbarui informasi pengguna dan hak akses.") }}</p>
      </div>
    </header>

    <form :action="meta.updateUrl" method="post" class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900" @submit="isSubmitting = true">
      <input type="hidden" name="_token" :value="meta.csrfToken" />
      <input type="hidden" name="_method" value="PUT" />
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <label for="username" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Username</label>
          <input
            id="username"
            name="username"
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
            name="email"
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
            name="first_name"
            v-model="form.first_name"
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            required
          />
          <p v-if="form.errors.first_name" class="mt-1 text-xs text-rose-500">{{ form.errors.first_name }}</p>
        </div>
        <div>
          <label for="last_name" class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ $t("Last Name (opsional)") }}</label>
          <input
            id="last_name"
            name="last_name"
            v-model="form.last_name"
            type="text"
            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm text-slate-700 focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
          />
          <p v-if="form.errors.last_name" class="mt-1 text-xs text-rose-500">{{ form.errors.last_name }}</p>
        </div>
      </div>

      <div>
        <label for="role" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Role</label>
        <WorkflowSelect id="role" v-model="form.role" :options="roles" aria-label="Role" :placeholder="$t('Pilih Role')" class="mt-1" required />
        <input type="hidden" name="role" :value="form.role" />
        <p v-if="form.errors.role" class="mt-1 text-xs text-rose-500">{{ form.errors.role }}</p>
      </div>

      <div>
        <label for="unit" class="block text-sm font-medium text-slate-700 dark:text-slate-200">Unit</label>
        <WorkflowSelect id="unit" v-model="form.unit" :options="unitOptions" aria-label="Unit" :placeholder="$t('Pilih Unit')" :required="unitRequired" class="mt-1" />
        <input type="hidden" name="unit" :value="form.unit" />
        <p v-if="form.errors.unit" class="mt-1 text-xs text-rose-500">{{ form.errors.unit }}</p>
        <p v-else-if="unitRequired" class="mt-1 text-xs text-slate-500">{{ $t("Unit wajib diisi bila akun dikelola Admin/User.") }}</p>
      </div>

      <div>
        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-200">{{ $t("Password (opsional)") }}</label>
        <input
          id="password"
          name="password"
          v-model="form.password"
          type="password"
          autocomplete="new-password"
          :placeholder="$t(&quot;Isi jika ingin mengubah password&quot;)"
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
          {{ $t("Hapus User") }}
        </button>

        <div class="ml-auto flex gap-2">
          <Link :href="backUrl" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-600 transition hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800">
            {{ $t("Batal") }}
          </Link>
          <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60" :disabled="isSubmitting">
            <span class="material-icons text-base">save</span>
            {{ isSubmitting ? $t('Menyimpan...') : $t("Simpan Perubahan") }}
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
              <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $t("Hapus User") }}</h3>
            </div>
            <p class="mt-3 text-sm text-slate-600 dark:text-slate-300">
              {{ $t("Anda yakin ingin menghapus akun") }} <strong>{{ user.username }}</strong>?
            </p>
            <div class="mt-6 flex justify-end gap-2">
              <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800" @click="closeDelete">
                {{ $t("Batal") }}
              </button>
              <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700" :disabled="deleteDialog.processing" @click="performDelete">
                <span class="material-icons text-base">delete</span>
                {{ $t("Hapus") }}
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
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import { useRoute } from 'ziggy-js';
import { Ziggy } from '@/ziggy';
import WorkflowSelect from '../../Components/WorkflowSelect.vue';

const props = defineProps({
  user: { type: Object, required: true },
  roles: { type: Array, default: () => [] },
  units: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
  meta: { type: Object, default: () => ({}) },
});

const route = useRoute(Ziggy);
const page = usePage();
const locale = computed(() => page.props.locale || Ziggy.defaults.locale);
const isSubmitting = ref(false);
const oldInput = props.meta.oldInput || {};

const form = useForm({
  username: oldInput.username ?? props.user.username ?? '',
  email: oldInput.email ?? props.user.email ?? '',
  first_name: oldInput.first_name ?? props.user.first_name ?? '',
  last_name: oldInput.last_name ?? props.user.last_name ?? '',
  role: oldInput.role ?? props.user.role ?? (props.roles[0]?.value ?? 'user'),
  password: '',
  unit: oldInput.unit ?? props.user.unit ?? '',
});
form.setError(page.props.errors || {});

const deleteDialog = ref({ open: false, processing: false });

function confirmDelete() {
  deleteDialog.value.open = true;
}

function closeDelete() {
  deleteDialog.value.open = false;
}

function performDelete() {
  deleteDialog.value.processing = true;
  router.delete(route('users.destroy', { locale: locale.value, user: props.user.id }), {
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
const unitOptions = computed(() => unitRequired.value ? props.units : [{ value: '', label: '—' }, ...props.units]);
const unitRequired = computed(() => props.meta?.unitRequired ?? true);
const can = computed(() => props.can ?? {});
const backUrl = computed(() => props.meta?.backUrl || props.meta?.from || route('users.report', { locale: locale.value }, false));

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
