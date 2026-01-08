<template>
  <div ref="wrapperRef" class="relative w-full">
    <button
      ref="triggerRef"
      type="button"
      class="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700 shadow-sm transition hover:border-indigo-400 focus:outline-none focus:ring-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
      :class="[open ? openButtonClass : '', accentButtonFocus]"
      :disabled="disabled"
      @click="toggle"
      @keydown.down.prevent="openAndHighlight(1)"
      @keydown.up.prevent="openAndHighlight(-1)"
      @keydown.enter.prevent="open ? selectHighlighted() : openDropdown()"
      @keydown.esc.prevent="close"
    >
      <span class="truncate">{{ selectedLabel }}</span>
      <span class="material-icons text-base text-slate-400 transition duration-200" :class="open ? iconOpenClass : ''">
        expand_more
      </span>
    </button>

    <div
      v-if="open"
      class="absolute left-0 right-0 top-full z-[9999] mt-2 w-full rounded-xl rounded-t-none border border-slate-200 bg-white p-3 shadow-2xl md:p-4 dark:border-slate-700 dark:bg-slate-900"
      :class="panelClass"
    >
      <div class="sticky top-0 z-10 mb-2 rounded-lg bg-white dark:bg-slate-900">
        <input
          ref="searchRef"
          v-model="searchTerm"
          type="text"
          :placeholder="searchPlaceholder"
          class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
          @keydown.down.prevent="moveHighlight(1)"
          @keydown.up.prevent="moveHighlight(-1)"
          @keydown.enter.prevent="selectHighlighted"
          @keydown.esc.prevent="close"
        />
      </div>

      <div class="max-h-72 overflow-y-auto pt-1">
        <template v-if="groupedList.length">
          <div v-for="(group, groupIndex) in groupedList" :key="`group-${group.label}-${groupIndex}`" class="mb-2 last:mb-0">
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800"
              @click="toggleGroup(group.label)"
            >
              <span>{{ group.label }}</span>
              <span class="material-icons text-base transition" :class="groupState[group.label] ? 'rotate-180' : ''">expand_more</span>
            </button>
            <div v-if="groupState[group.label]" class="mt-1 space-y-1">
              <button
                v-for="option in group.options"
                :key="`${group.label}-${option.value}`"
                type="button"
                class="flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition"
                :class="optionClasses(option, option.index)"
                @click="select(option)"
                @mouseenter="highlight(option.index)"
              >
                <span class="truncate">{{ option.label }}</span>
                <span v-if="isSelected(option)" class="material-icons text-sm">check</span>
              </button>
            </div>
          </div>
        </template>

        <p v-if="!groupedList.length" class="px-3 py-4 text-center text-sm text-slate-500 dark:text-slate-400">
          No results
        </p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: [String, Number, null], default: null },
  options: { type: Array, default: () => [] }, // supports flat or grouped { label, value, children }
  suggested: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
  accent: { type: String, default: 'indigo' },
  searchPlaceholder: { type: String, default: 'Search...' },
});

const emit = defineEmits(['update:modelValue']);

const open = ref(false);
const searchTerm = ref('');
const highlightedIndex = ref(-1);
const wrapperRef = ref(null);
const searchRef = ref(null);
const triggerRef = ref(null);

const groupState = ref({});

const accentMap = {
  indigo: {
    border: 'border-indigo-400',
    borderDark: 'dark:border-indigo-500',
    focus: 'focus:ring-indigo-200 dark:focus:ring-indigo-500/40',
    icon: 'rotate-180 text-indigo-500',
    selected: 'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200',
    hover: 'hover:border-indigo-400',
  },
  subtle: {
    border: 'border-slate-200',
    borderDark: 'dark:border-slate-700',
    focus: 'focus:ring-slate-200 dark:focus:ring-slate-700/40',
    icon: 'rotate-180 text-slate-500',
    selected: 'bg-slate-100 text-slate-700 dark:bg-slate-800/60 dark:text-slate-100',
    hover: 'hover:border-slate-300 dark:hover:border-slate-600',
  },
};

const accentConfig = computed(() => accentMap[props.accent] ?? accentMap.indigo);
const openButtonClass = computed(() => `${accentConfig.value.border} ${accentConfig.value.borderDark}`);
const accentButtonFocus = computed(() => accentConfig.value.focus);
const iconOpenClass = computed(() => accentConfig.value.icon);
const panelClass = computed(() => `${accentConfig.value.border} ${accentConfig.value.borderDark}`);
const optionClasses = (option, index) => {
  const base = ['flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm transition'];
  if (isSelected(option)) {
    base.push(accentConfig.value.selected);
  } else {
    base.push('hover:bg-slate-100 dark:hover:bg-slate-800', accentConfig.value.hover);
  }
  if (index === highlightedIndex.value) {
    base.push('ring-1 ring-indigo-200 dark:ring-indigo-500/40');
  }
  return base;
};

const normalizeOption = option => {
  if (option && typeof option === 'object') {
    return {
      label: option.label ?? option.value ?? '',
      value: option.value ?? option.label ?? '',
      children: Array.isArray(option.children) ? option.children : null,
    };
  }
  return { label: String(option ?? ''), value: String(option ?? '') };
};

const normalizedOptions = computed(() =>
  (props.options ?? []).map(normalizeOption).filter(option => option.value !== '')
);

const suggestedOptions = computed(() => {
  const seen = new Set();
  return (props.suggested ?? [])
    .map(normalizeOption)
    .filter(option => option.value !== '')
    .filter(option => {
      const key = String(option.value);
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });
});

const flatOptions = computed(() => {
  const flattened = [];
  const pushOption = option => flattened.push(normalizeOption(option));
  normalizedOptions.value.forEach(option => {
    if (option.children?.length) {
      option.children.forEach(child => pushOption(child));
    } else {
      pushOption(option);
    }
  });
  return flattened;
});

const matchesSearch = option => {
  const search = searchTerm.value.trim().toLowerCase();
  if (!search) return true;
  const label = option.label?.toLowerCase?.() ?? '';
  const value = option.value?.toString?.().toLowerCase?.() ?? '';
  return label.includes(search) || value.includes(search);
};

const combinedList = computed(() => {
  // keep flat index for highlighting
  const list = [];
  flatOptions.value.forEach((opt, idx) => {
    if (matchesSearch(opt)) {
      list.push({ ...opt, index: idx });
    }
  });
  return list;
});

const groupedList = computed(() => {
  const groups = [];
  normalizedOptions.value.forEach((group, groupIndex) => {
    if (group.children?.length) {
      const children = group.children
        .map(child => normalizeOption(child))
        .map(child => ({
          ...child,
          index: combinedList.value.findIndex(item => item.value === child.value),
        }))
        .filter(item => item.index >= 0 && matchesSearch(item));
      if (children.length) {
        groups.push({ label: group.label, options: children, groupIndex });
      }
    } else if (matchesSearch(group)) {
      groups.push({
        label: group.label,
        options: [
          {
            ...group,
            index: combinedList.value.findIndex(item => item.value === group.value),
          },
        ],
        groupIndex,
      });
    }
  });
  return groups;
});

const syncGroupState = () => {
  const state = {};
  groupedList.value.forEach(group => {
    state[group.label] = searchTerm.value ? group.options.length > 0 : false;
  });
  groupState.value = state;
};

const selectedLabel = computed(() => {
  const current = combinedList.value.find(option => isSelected(option));
  if (current) return current.label;
  const fallback = flatOptions.value.find(option => isSelected(option));
  return fallback?.label ?? 'Pilih opsi';
});

const highlight = index => {
  highlightedIndex.value = index;
};

const moveHighlight = step => {
  if (!combinedList.value.length) {
    highlightedIndex.value = -1;
    return;
  }
  const current = highlightedIndex.value < 0 ? 0 : highlightedIndex.value;
  const next = (current + step + combinedList.value.length) % combinedList.value.length;
  highlightedIndex.value = next;
};

const selectHighlighted = () => {
  const option = combinedList.value[highlightedIndex.value];
  if (option) {
    select(option);
  }
};

const openDropdown = () => {
  if (props.disabled) return;
  open.value = true;
  searchTerm.value = '';
  const currentIndex = combinedList.value.findIndex(option => isSelected(option));
  highlightedIndex.value = currentIndex >= 0 ? currentIndex : 0;
  syncGroupState();
  nextTick(() => searchRef.value?.focus());
};

const toggle = () => {
  if (open.value) {
    close();
  } else {
    openDropdown();
  }
};

const close = () => {
  open.value = false;
  highlightedIndex.value = -1;
};

const select = option => {
  emit('update:modelValue', option.value);
  close();
  nextTick(() => triggerRef.value?.focus());
};

const isSelected = option => String(option.value) === String(props.modelValue ?? '');

const optionIndex = index => index;

const openAndHighlight = step => {
  if (!open.value) {
    openDropdown();
    moveHighlight(step);
  } else {
    moveHighlight(step);
  }
};

const handleOutsideClick = event => {
  if (!open.value) return;
  if (wrapperRef.value && !wrapperRef.value.contains(event.target)) {
    close();
  }
};

const toggleGroup = label => {
  groupState.value = { ...groupState.value, [label]: !groupState.value[label] };
};

watch(searchTerm, () => {
  highlightedIndex.value = combinedList.value.length ? 0 : -1;
  syncGroupState();
});

onMounted(() => {
  document.addEventListener('click', handleOutsideClick, true);
  syncGroupState();
});

onBeforeUnmount(() => {
  document.removeEventListener('click', handleOutsideClick, true);
});
</script>
