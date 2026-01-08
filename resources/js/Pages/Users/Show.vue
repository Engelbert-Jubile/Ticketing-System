<template>
  <div class="mx-auto max-w-3xl space-y-6 px-4 py-6">
    <header class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-slate-100">User Detail</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Informasi lengkap akun pengguna.</p>
      </div>
      <Link :href="backUrl" class="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline dark:text-blue-300">
        <span class="material-icons text-base">arrow_back</span>
        Kembali
      </Link>
    </header>

    <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="flex items-start justify-between gap-4">
        <div class="space-y-4">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Username</p>
            <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ user.username }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Nama Lengkap</p>
            <p class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ user.name || '—' }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Email</p>
            <a :href="`mailto:${user.email}`" class="text-lg font-semibold text-blue-600 hover:underline dark:text-blue-300">{{ user.email }}</a>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300">Roles</p>
            <div class="mt-2 flex flex-wrap gap-2">
              <span
                v-for="role in user.roles"
                :key="role.name"
                :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', role.badge]"
              >
                {{ role.label }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex flex-col gap-2">
          <Link
            v-if="can.update"
            :href="route('users.edit', { user: user.id, from: backUrl })"
            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
          >
            <span class="material-icons text-base">edit</span>
            Edit
          </Link>
          <button
            v-if="can.delete"
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-rose-200 px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50 dark:border-rose-900/40 dark:text-rose-300 dark:hover:bg-rose-900/20"
            @click="confirmDelete"
          >
            <span class="material-icons text-base">delete</span>
            Hapus
          </button>
        </div>
      </div>
    </section>

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
import { Link, router } from '@inertiajs/vue3';
import { useRoute } from 'ziggy-js';
import { Ziggy } from '@/ziggy';

const props = defineProps({
  user: { type: Object, required: true },
  can: { type: Object, default: () => ({}) },
  meta: { type: Object, default: () => ({}) },
});

const route = useRoute(Ziggy);

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

const user = computed(() => props.user);
const can = computed(() => props.can ?? {});
const backUrl = computed(() => props.meta?.backUrl || route('users.report'));
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
