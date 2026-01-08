<template>
  <div class="nav-group" :class="{ 'nav-group--active': active }">
    <button type="button" class="nav-btn" :class="{ 'nav-btn--active': expanded || active }" @click="emit('toggle')">
      <span class="material-icons">{{ item.icon }}</span>

      <div v-if="sidebarOpen" class="nav-main">
        <span class="nav-label">{{ item.label }}</span>
        <span
          v-if="item.indicator && indicatorType === 'pill'"
          class="nav-pill"
          :class="`nav-pill--${indicatorVariant}`"
        >
          {{ item.indicator.text }}
        </span>
      </div>

      <span
        v-else-if="item.indicator && indicatorType === 'dot'"
        class="nav-dot"
        :class="`nav-dot--${indicatorVariant}`"
      ></span>

      <span v-if="sidebarOpen" class="material-icons nav-caret" :class="{ 'rotate-180': expanded }">expand_more</span>
    </button>

    <transition name="collapse">
      <ul v-if="expanded && (sidebarOpen || !isDesktop)" class="nav-sub">
        <li v-for="child in item.children" :key="child.key">
          <button
            type="button"
            class="nav-sub__link"
            :class="{ 'nav-sub__link--active': childActive(child) }"
            @click="emit('navigate', child.href)"
          >
            <span class="nav-sub__label">{{ child.label }}</span>
            <span v-if="sidebarOpen && child.badge !== undefined" class="nav-sub__badge">{{ child.badge }}</span>
          </button>
        </li>
      </ul>
    </transition>
  </div>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  currentUrl: {
    type: String,
    required: true,
  },
  sidebarOpen: {
    type: Boolean,
    default: true,
  },
  expanded: {
    type: Boolean,
    default: false,
  },
  active: {
    type: Boolean,
    default: false,
  },
  isDesktop: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['toggle', 'navigate'])

const indicatorType = computed(() => props.item?.indicator?.type ?? 'dot')
const indicatorVariant = computed(() => props.item?.indicator?.variant ?? 'info')

const childActive = child => {
  if (typeof child.match === 'function') {
    return child.match(props.currentUrl)
  }
  if (child.exact) {
    return props.currentUrl === child.href
  }
  return props.currentUrl.startsWith(child.href)
}
</script>

<style scoped>
.nav-group {
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.nav-group--active > .nav-btn {
  background: var(--sidebar-active-bg, #2563eb);
  color: #ffffff;
}

.nav-btn {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.6rem 0.9rem;
  border-radius: 0.9rem;
  color: inherit;
  background: transparent;
  border: none;
  font-weight: 600;
  transition: background 0.2s ease, color 0.2s ease;
}

.nav-main {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1 1 auto;
  justify-content: space-between;
}

.nav-btn:hover {
  background: var(--sidebar-active-bg, #2563eb);
  color: #ffffff;
  box-shadow: 0 18px 38px -24px rgba(37, 99, 235, 0.55);
}

.nav-btn--active {
  background: var(--sidebar-active-bg, #2563eb);
  color: #ffffff;
  box-shadow: 0 16px 34px -24px rgba(37, 99, 235, 0.5);
}

.nav-btn:hover .material-icons,
.nav-btn:hover .nav-label,
.nav-btn--active .material-icons,
.nav-btn--active .nav-label,
.nav-group--active > .nav-btn .material-icons,
.nav-group--active > .nav-btn .nav-label {
  color: #ffffff;
}

.nav-label {
  flex: 1 1 auto;
  text-align: left;
}

.nav-caret {
  transition: transform 0.2s ease;
}

.nav-sub {
  margin-top: 0.25rem;
  padding-left: 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.nav-sub__link {
  width: 100%;
  text-align: left;
  padding: 0.45rem 0.75rem;
  border-radius: 0.75rem;
  border: none;
  background: transparent;
  color: inherit;
  font-weight: 500;
  transition: background 0.2s ease;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.nav-sub__link:hover {
  background: var(--sidebar-active-bg, #2563eb);
  color: #ffffff;
}

.nav-sub__link--active {
  background: var(--sidebar-active-bg, #2563eb);
  color: #ffffff;
  box-shadow: 0 12px 30px -20px rgba(37, 99, 235, 0.45);
}

.nav-sub__link:hover .nav-sub__label,
.nav-sub__link--active .nav-sub__label,
.nav-sub__link:hover .nav-sub__badge,
.nav-sub__link--active .nav-sub__badge {
  color: #ffffff;
}

.nav-sub__badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.75rem;
  padding: 0 0.45rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 600;
  color: #1d4ed8;
  background: rgba(37, 99, 235, 0.12);
}

.dark .nav-sub__badge {
  color: #bfdbfe;
  background: rgba(96, 165, 250, 0.18);
}

.nav-dot {
  width: 0.6rem;
  height: 0.6rem;
  border-radius: 999px;
  margin-left: auto;
  background: rgba(37, 99, 235, 0.45);
}

.nav-dot--danger {
  background: #f97316;
}

.nav-dot--info {
  background: #38bdf8;
}

.nav-dot--success {
  background: #22c55e;
}

.nav-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0.1rem 0.55rem;
  border-radius: 999px;
  font-size: 0.68rem;
  font-weight: 600;
  background: var(--sidebar-active-bg, #2563eb);
  color: #ffffff;
  box-shadow: 0 12px 28px -18px rgba(37, 99, 235, 0.5);
}

.nav-pill--danger {
  background: rgba(249, 115, 22, 0.16);
  color: #c2410c;
}

.nav-pill--info {
  background: rgba(59, 130, 246, 0.16);
  color: #1d4ed8;
}

.nav-pill--success {
  background: rgba(16, 185, 129, 0.16);
  color: #047857;
}

.dark .nav-group--active > .nav-btn,
.dark .nav-btn:hover,
.dark .nav-btn--active,
.dark .nav-sub__link:hover,
.dark .nav-sub__link--active {
  color: #e0f2ff;
}

.collapse-enter-active,
.collapse-leave-active {
  transition: all 0.2s ease;
}

.collapse-enter-from,
.collapse-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}
</style>
