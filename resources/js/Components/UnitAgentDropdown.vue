<template>
  <div class="space-y-1" ref="rootRef">
    <label v-if="label" class="block text-sm font-semibold text-slate-600 dark:text-slate-300">{{ label }}</label>
    <div class="relative">
      <button
        type="button"
        class="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-white px-3 py-2 text-left text-sm text-slate-700 shadow-sm transition hover:border-blue-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-blue-400"
        :disabled="disabled"
        @click="toggleDropdown"
      >
        <span class="line-clamp-1">{{ selectedLabel || placeholder }}</span>
        <span class="material-icons text-base" :class="{ 'rotate-180': dropdownOpen }">expand_more</span>
      </button>

      <transition name="fade">
        <div
          v-if="dropdownOpen"
          class="absolute z-30 mt-2 w-full overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900"
        >
          <div class="border-b border-slate-100 bg-slate-50 px-3 py-2 dark:border-slate-800 dark:bg-slate-800/40">
            <div class="flex items-center justify-between gap-3">
              <div>
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Unit tersedia</p>
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-100">{{ unitCount }} Unit · {{ filteredOptions.length }} Agent</p>
              </div>
              <button
                type="button"
                class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600 shadow-sm hover:border-blue-400 hover:text-blue-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                @click.stop="toggleSearch"
              >
                <span class="material-icons text-base">{{ searchOpen ? 'close' : 'search' }}</span>
                {{ searchOpen ? 'Tutup' : 'Cari' }}
              </button>
            </div>
          </div>

          <transition name="fade">
            <div v-if="searchOpen" class="border-b border-slate-100 bg-white px-3 pb-3 pt-2 dark:border-slate-800 dark:bg-slate-900">
              <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-600 focus-within:border-blue-400 focus-within:ring-1 focus-within:ring-blue-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100">
                <span class="material-icons text-base text-slate-400">search</span>
                <input
                  v-model="searchQuery"
                  type="text"
                  class="w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400 dark:text-slate-100"
                  placeholder="Cari nama agent, email, atau unit"
                />
                <button v-if="searchQuery" type="button" class="text-xs text-slate-400 hover:text-rose-500" @click="clearSearch">Reset</button>
              </div>
            </div>
          </transition>

          <template v-if="groupedOptions.length">
            <div class="max-h-80 overflow-y-auto bg-white py-3 dark:bg-slate-900">
              <div v-for="group in groupedOptions" :key="group.key" class="px-3">
                <button
                  type="button"
                  class="mb-2 flex w-full items-center justify-between rounded-2xl border border-slate-100 bg-slate-50 px-4 py-2 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-200 hover:bg-blue-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100"
                  :class="{ 'border-blue-300 bg-blue-50/80 dark:border-blue-500/60 dark:bg-slate-800/80': expandedUnit === group.key }"
                  @click="toggleUnit(group.key)"
                >
                  <div>
                    <p class="text-base font-bold text-slate-800 dark:text-slate-100">{{ group.unitLabel }}</p>
                    <p class="text-xs font-normal text-slate-400">{{ group.agents.length }} agent tersedia</p>
                  </div>
                  <span class="material-icons text-base">{{ expandedUnit === group.key ? 'expand_less' : 'expand_more' }}</span>
                </button>
                <div v-if="expandedUnit === group.key" class="space-y-1 border-l border-dashed border-slate-200 pl-4 dark:border-slate-700">
                  <button
                    v-for="agent in group.agents"
                    :key="agent.id"
                    type="button"
                    class="w-full rounded-xl px-3 py-2 text-left text-sm ring-1 ring-transparent transition hover:bg-blue-50 hover:ring-blue-200 dark:hover:bg-slate-800"
                    :class="{ 'bg-blue-100 font-semibold text-blue-900 ring-blue-200 dark:bg-slate-800/70 dark:text-slate-100': isSelected(agent.id) }"
                    @click="selectAgent(agent.id)"
                  >
                    <p class="flex items-center gap-2">
                      <span class="material-icons text-xs text-slate-400">person</span>
                      <span>{{ agent.label }}</span>
                    </p>
                    <p class="text-xs text-slate-400">{{ agent.agent_label || 'User' }} · {{ agent.email || '—' }}</p>
                  </button>
                </div>
              </div>
            </div>
          </template>
          <p v-else class="px-4 py-4 text-sm text-slate-500">Tidak ada agent yang cocok dengan pencarian.</p>
        </div>
      </transition>
    </div>
    <p v-if="hint" class="text-xs text-slate-400">{{ hint }}</p>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: [Number, String, null], default: null },
  options: { type: Array, default: () => [] },
  label: { type: String, default: '' },
  placeholder: { type: String, default: 'Pilih agent berdasarkan unit' },
  disabled: { type: Boolean, default: false },
  hint: { type: String, default: '' },
});

const emit = defineEmits(['update:modelValue']);

const dropdownOpen = ref(false);
const expandedUnit = ref(null);
const rootRef = ref(null);
const searchOpen = ref(false);
const searchQuery = ref('');

const unitPlaceholder = 'Unit Tidak Tercatat';

const normalizedOptions = computed(() => Array.isArray(props.options) ? props.options : []);

const filteredOptions = computed(() => {
  const term = searchQuery.value.trim().toLowerCase();
  if (!term) {
    return normalizedOptions.value;
  }

  return normalizedOptions.value.filter(option => {
    const haystacks = [option.label, option.email, option.unit, option.agent_label]
      .filter(Boolean)
      .map(value => String(value).toLowerCase());

    return haystacks.some(value => value.includes(term));
  });
});

const groupedOptions = computed(() => {
  const groups = new Map();
  filteredOptions.value.forEach(option => {
    const key = option.unit && option.unit.trim() !== '' ? option.unit : '__no_unit__';
    if (!groups.has(key)) {
      groups.set(key, {
        key,
        unitLabel: key === '__no_unit__' ? unitPlaceholder : option.unit,
        agents: [],
      });
    }
    groups.get(key).agents.push(option);
  });

  return Array.from(groups.values()).sort((a, b) => a.unitLabel.localeCompare(b.unitLabel));
});

const unitCount = computed(() => {
  const set = new Set();
  normalizedOptions.value.forEach(option => {
    const label = option.unit && option.unit.trim() !== '' ? option.unit : unitPlaceholder;
    set.add(label);
  });

  return set.size;
});

const selectedOption = computed(() => {
  const currentId = props.modelValue === '' ? null : props.modelValue;
  return normalizedOptions.value.find(option => String(option.id) === String(currentId)) || null;
});

const selectedLabel = computed(() => {
  if (!selectedOption.value) {
    return '';
  }

  const unitLabel = selectedOption.value.unit && selectedOption.value.unit.trim() !== ''
    ? `[${selectedOption.value.unit}]`
    : `[${unitPlaceholder}]`;

  const roleLabel = selectedOption.value.agent_label && selectedOption.value.agent_label !== '—'
    ? `(${selectedOption.value.agent_label})`
    : '';

  const base = `${unitLabel} ${selectedOption.value.label}`.trim();

  return roleLabel ? `${base} ${roleLabel}` : base;
});

function toggleDropdown() {
  if (props.disabled) {
    return;
  }
  dropdownOpen.value = !dropdownOpen.value;
}

function toggleUnit(unitKey) {
  expandedUnit.value = expandedUnit.value === unitKey ? null : unitKey;
}

function toggleSearch() {
  searchOpen.value = !searchOpen.value;
  if (!searchOpen.value) {
    searchQuery.value = '';
  }
}

function clearSearch() {
  searchQuery.value = '';
}

function selectAgent(agentId) {
  emit('update:modelValue', agentId);
  dropdownOpen.value = false;
}

function isSelected(agentId) {
  return String(agentId) === String(props.modelValue ?? '');
}

function handleOutsideClick(event) {
  if (!dropdownOpen.value) {
    return;
  }
  if (rootRef.value && !rootRef.value.contains(event.target)) {
    dropdownOpen.value = false;
  }
}

onMounted(() => {
  if (typeof window !== 'undefined') {
    window.addEventListener('click', handleOutsideClick);
  }
});

onBeforeUnmount(() => {
  if (typeof window !== 'undefined') {
    window.removeEventListener('click', handleOutsideClick);
  }
});

watch(dropdownOpen, value => {
  if (value && !expandedUnit.value && groupedOptions.value.length) {
    expandedUnit.value = groupedOptions.value[0].key;
  }

  if (!value) {
    searchOpen.value = false;
    searchQuery.value = '';
  }
});

watch(
  () => props.options,
  () => {
    if (!props.options.length) {
      dropdownOpen.value = false;
      expandedUnit.value = null;
      searchQuery.value = '';
      searchOpen.value = false;
    }
  },
  { deep: true }
);
</script>

<style scoped>
.material-icons {
  font-size: inherit;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease, transform 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
