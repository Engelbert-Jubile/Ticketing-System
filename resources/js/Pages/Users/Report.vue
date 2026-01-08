<template>
  <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 lg:px-0">
    <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-3xl font-bold text-slate-900 dark:text-slate-100">User Report</h1>
        <p class="text-sm text-slate-500 dark:text-slate-300">Kelola akun pengguna dan hak akses di sistem.</p>
      </div>

      <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
        <div class="relative w-full sm:w-72">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
            <span class="material-icons text-base">search</span>
          </span>
          <input
            ref="searchField"
            v-model="searchInput"
            type="search"
            placeholder="Cari username, nama, email…"
            class="w-full rounded-xl border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-700 shadow-sm transition focus:border-blue-500 focus:outline-none dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200"
            @keyup.enter.prevent="applySearch"
          />
        </div>

        <div class="sm:w-64">
          <MultiSelect
            v-model="selectedRoles"
            :options="roles"
            placeholder="Filter Role"
          />
        </div>

        <Dropdown width-class="w-40" trigger-class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 shadow-sm hover:bg-slate-50 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-200">
          <template #trigger>
            <span class="flex items-center gap-2">
              <span class="material-icons text-base">tune</span>
              {{ perPage }} / page
            </span>
          </template>
          <template #default>
            <button
              v-for="option in perPageOptions"
              :key="option"
              type="button"
              class="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-700/60"
              :class="option === perPage ? 'font-semibold text-blue-600 dark:text-blue-300' : 'text-slate-600 dark:text-slate-200'"
              @click="changePerPage(option)"
            >
              <span>{{ option }}</span>
              <span v-if="option === perPage" class="material-icons text-sm">check</span>
            </button>
          </template>
        </Dropdown>

        <Link
          v-if="can.create"
          :href="route('users.create')"
          class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"
        >
          <span class="material-icons text-base mr-1">person_add</span>
          Create User
        </Link>
      </div>
    </header>

    <section v-if="flash.success" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-900/30 dark:text-emerald-200">
      {{ flash.success }}
    </section>
    <section v-else-if="flash.error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-900/60 dark:bg-rose-900/30 dark:text-rose-200">
      {{ flash.error }}
    </section>

    <section class="rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
      <div class="overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-700">
          <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:bg-slate-800 dark:text-slate-300">
            <tr>
              <th class="px-4 py-3 text-left">User</th>
              <th class="px-4 py-3 text-left hidden md:table-cell">Nama Lengkap</th>
              <th class="px-4 py-3 text-left">Email</th>
              <th class="px-4 py-3 text-left">Unit</th>
              <th class="px-4 py-3 text-left">Role</th>
              <th class="px-4 py-3 text-right">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white dark:divide-slate-700 dark:bg-slate-900/60">
            <tr v-for="user in rows" :key="user.id" class="transition hover:bg-slate-50/70 dark:hover:bg-slate-800/60">
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="grid h-10 w-10 place-items-center rounded-full bg-slate-100 text-base font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    {{ user.initials }}
                  </div>
                  <div>
                    <div class="font-semibold text-slate-900 dark:text-slate-100">{{ user.username }}</div>
                    <Link :href="user.links.show" class="text-xs text-blue-600 hover:underline dark:text-blue-400">Detail</Link>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3 hidden text-slate-600 dark:text-slate-300 md:table-cell">{{ user.name || '—' }}</td>
              <td class="px-4 py-3">
                <a :href="`mailto:${user.email}`" class="text-blue-600 hover:underline dark:text-blue-400">{{ user.email }}</a>
              </td>
              <td class="px-4 py-3 text-slate-600 dark:text-slate-300">{{ user.unit || '—' }}</td>
              <td class="px-4 py-3">
                <div class="flex flex-wrap gap-1">
                  <span
                    v-for="role in user.roles"
                    :key="role.name"
                    :class="['inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold', role.badge]"
                  >
                    {{ role.label }}
                  </span>
                </div>
              </td>
              <td class="px-4 py-3 text-right">
                <Dropdown v-if="user.can.update || user.can.delete" align="right" width-class="w-44">
                  <template #trigger>
                    <span class="material-icons text-base">more_vert</span>
                  </template>
                  <template #default>
                    <button
                      v-if="user.can.update && user.links.edit"
                      type="button"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left hover:bg-slate-100 dark:hover:bg-slate-700/60"
                      @click="goTo(user.links.edit)"
                    >
                      <span class="material-icons text-base">edit</span>
                      Edit
                    </button>
                    <button
                      v-if="user.can.delete"
                      type="button"
                      class="flex w-full items-center gap-2 px-3 py-2 text-left text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                      @click="confirmDelete(user)"
                    >
                      <span class="material-icons text-base">delete</span>
                      Delete
                    </button>
                  </template>
                </Dropdown>
              </td>
            </tr>
            <tr v-if="!rows.length">
              <td colspan="6" class="px-4 py-10 text-center text-slate-400">Tidak ada data.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <footer v-if="users.links && users.links.length > 3" class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-700 dark:bg-slate-900">
        <button
          v-for="link in users.links"
          :key="link.label"
          type="button"
          class="rounded-lg border px-3 py-1.5 text-sm transition"
          :class="link.active
            ? 'border-blue-500 bg-blue-500 text-white shadow-sm'
            : link.url
              ? 'border-slate-300 bg-white text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800'
              : 'border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500'"
          :disabled="!link.url"
          @click="goTo(link.url)"
          v-html="link.label"
        />
      </footer>
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
              Anda yakin ingin menghapus akun <strong>{{ deleteDialog.user?.username }}</strong>?
              <span class="block text-xs text-slate-400">Tindakan ini tidak dapat dibatalkan.</span>
            </p>
            <div class="mt-6 flex justify-end gap-2">
              <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-100 dark:border-slate-600 dark:text-slate-300 dark:hover:bg-slate-800" @click="closeDelete">
                Batal
              </button>
              <button type="button" class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700" @click="performDelete" :disabled="deleteDialog.processing">
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { route as ziggyRoute } from 'ziggy-js';
import { Ziggy } from '@/ziggy';
import Dropdown from '@/Components/Dropdown.vue';
import MultiSelect from '@/Components/MultiSelect.vue';

const props = defineProps({
  users: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  roles: { type: Array, default: () => [] },
  can: { type: Object, default: () => ({}) },
});

const route = (name, params = undefined, absolute = true) => ziggyRoute(name, params, absolute, Ziggy);

const perPageOptions = [10, 15, 25, 50, 100];

const searchField = ref(null);
const search = ref(props.filters.q || '');
const searchInput = ref(search.value);
const selectedRoles = ref(Array.isArray(props.filters.roles) ? [...props.filters.roles] : []);
const perPage = ref(Number(props.filters.per_page || 15));

const searchTimer = ref(null);
const isFiltering = ref(false);
const syncingFilters = ref(false);
const activeRequestId = ref(0);
let requestCounter = 0;

const normalizeFilters = filters => ({
  q: typeof filters?.q === 'string' ? filters.q : '',
  roles: Array.isArray(filters?.roles) ? [...filters.roles] : [],
  per_page: Number(filters?.per_page || 15),
});

const arraysEqual = (a, b) => a.length === b.length && a.every((value, index) => value === b[index]);
const filtersEqual = (a, b) => a.q === b.q && a.per_page === b.per_page && arraysEqual(a.roles, b.roles);

const latestSubmitted = ref(normalizeFilters(props.filters));
const pendingQuery = ref(null);

function syncLocalFilters(nextFilters) {
  syncingFilters.value = true;
  if (search.value !== nextFilters.q) {
    search.value = nextFilters.q;
  }
  const shouldUpdateInput =
    typeof document === 'undefined' || document.activeElement !== searchField.value;
  if (shouldUpdateInput && searchInput.value !== nextFilters.q) {
    searchInput.value = nextFilters.q;
  }
  if (!arraysEqual(selectedRoles.value, nextFilters.roles)) {
    selectedRoles.value = [...nextFilters.roles];
  }
  if (perPage.value !== nextFilters.per_page) {
    perPage.value = nextFilters.per_page;
  }
  syncingFilters.value = false;
}

watch(
  () => props.filters,
  value => {
    if (pendingQuery.value) {
      return;
    }
    const nextFilters = normalizeFilters(value);
    latestSubmitted.value = nextFilters;
    syncLocalFilters(nextFilters);
  }
);

watch(
  searchInput,
  value => {
    if (syncingFilters.value) {
      return;
    }
    if (searchTimer.value) {
      clearTimeout(searchTimer.value);
    }
    searchTimer.value = setTimeout(() => {
      searchTimer.value = null;
      const normalized = value?.trim() ?? '';
      if (normalized === (search.value?.trim() ?? '')) {
        return;
      }
      search.value = value;
      triggerFilters();
    }, 500);
  }
);

watch(
  selectedRoles,
  () => {
    if (syncingFilters.value) {
      return;
    }
    triggerFilters();
  },
  { deep: true }
);

function triggerFilters() {

  const params = {
    per_page: perPage.value,
    q: search.value?.trim() || undefined,
    roles: selectedRoles.value.length ? [...selectedRoles.value] : undefined,
  };

  const payload = normalizeFilters({
    per_page: params.per_page,
    q: params.q ?? '',
    roles: params.roles ?? [],
  });
  if (pendingQuery.value && filtersEqual(payload, pendingQuery.value.filters)) {
    return;
  }
  if (!pendingQuery.value && filtersEqual(payload, latestSubmitted.value)) {
    return;
  }

  const currentRequestId = ++requestCounter;
  activeRequestId.value = currentRequestId;
  pendingQuery.value = { token: currentRequestId, filters: payload };
  isFiltering.value = true;

  router.get(route('users.report'), params, {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    onSuccess: page => {
      if (currentRequestId !== activeRequestId.value) {
        return;
      }
      const nextFilters = normalizeFilters(page?.props?.filters ?? {});
      latestSubmitted.value = nextFilters;
      pendingQuery.value = null;
      syncLocalFilters(nextFilters);
    },
    onCancel: () => {
      if (currentRequestId === activeRequestId.value) {
        pendingQuery.value = null;
      }
    },
    onError: () => {
      if (currentRequestId === activeRequestId.value) {
        pendingQuery.value = null;
      }
    },
    onFinish: () => {
      if (activeRequestId.value === currentRequestId) {
        isFiltering.value = false;
      }
      if (searchTimer.value) {
        clearTimeout(searchTimer.value);
        searchTimer.value = null;
      }
    },
  });
}

function applySearch() {
  if (syncingFilters.value) {
    return;
  }
  if (searchTimer.value) {
    clearTimeout(searchTimer.value);
    searchTimer.value = null;
  }
  const normalized = searchInput.value?.trim() ?? '';
  if (normalized === (search.value?.trim() ?? '')) {
    return;
  }
  search.value = searchInput.value;
  triggerFilters();
}

function changePerPage(value) {
  if (perPage.value === value) return;
  perPage.value = value;
  triggerFilters();
}

function goTo(url) {
  if (!url) return;
  router.visit(url, { preserveScroll: true });
}

const rows = computed(() => props.users?.data ?? []);

const deleteDialog = ref({ open: false, user: null, processing: false });

function confirmDelete(user) {
  deleteDialog.value = { open: true, user, processing: false };
}

function closeDelete() {
  deleteDialog.value.open = false;
}

function performDelete() {
  if (!deleteDialog.value.user) return;
  deleteDialog.value.processing = true;
  router.delete(route('users.destroy', deleteDialog.value.user.id), {
    preserveScroll: true,
    onFinish: () => {
      deleteDialog.value.processing = false;
      deleteDialog.value.open = false;
    },
  });
}

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeydown);
  if (searchTimer.value) {
    clearTimeout(searchTimer.value);
  }
});

function handleKeydown(event) {
  if (event.key === 'Escape' && deleteDialog.value.open) {
    closeDelete();
  }
}

const page = usePage();
const flash = computed(() => page.props.flash || {});

const can = computed(() => props.can || {});

const users = computed(() => props.users || { data: [], links: [] });

const roles = computed(() => props.roles ?? []);
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

.fade-enter-to,
.fade-leave-from {
  opacity: 1;
}

.material-icons {
  font-size: inherit;
}
</style>
