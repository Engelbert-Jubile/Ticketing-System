<template>
  <aside
    class="sidebar"
    :class="[
      sidebarOpen ? 'sidebar--open' : 'sidebar--closed',
      sidebarLocked ? 'sidebar--locked' : '',
      isDesktop ? 'sidebar--desktop' : 'sidebar--mobile',
      hovering ? 'sidebar--hovering' : '',
    ]"
    @mouseenter="handleMouseEnter"
    @mouseleave="handleMouseLeave"
  >
    <div class="sidebar__inner">
      <div class="sidebar__header">
        <span v-if="sidebarOpen" class="sidebar__title">{{ t('nav.title') }}</span>
        <button
          type="button"
          class="hb-btn"
          :class="{ 'hb-btn--locked': sidebarLocked }"
          :aria-pressed="String(sidebarLocked)"
          :title="sidebarLocked ? 'Buka kunci sidebar' : 'Kunci sidebar'"
          @click="toggleLock"
        >
        <span class="material-icons hb-icon" aria-hidden="true">
          {{ sidebarLocked ? 'lock' : 'lock_open' }}
        </span>
        </button>
      </div>

      <div class="sidebar__body">
        <nav class="sidebar__menu">
          <ul>
          <li
            v-for="item in navItems"
            :key="item.key"
            class="nav-item"
            :class="navItemClass(item)"
          >
            <SidebarLink
              v-if="item.type === 'link'"
              :item="item"
              :active="isActive(item)"
              :sidebar-open="sidebarOpen"
              @navigate="handleNavigate"
            />

            <SidebarGroup
              v-else-if="item.type === 'group'"
              :item="item"
              :sidebar-open="sidebarOpen"
              :expanded="isGroupExpanded(item.key)"
              :active="isGroupActive(item)"
              :current-url="currentUrl"
              :is-desktop="isDesktop"
              @toggle="toggleGroup(item.key)"
              @navigate="handleNavigate"
            />

            <button
              v-else-if="item.type === 'logout'"
              type="button"
              class="nav-btn nav-btn--logout"
              @click="logout"
            >
              <span class="material-icons">logout</span>
              <span v-if="sidebarOpen" class="nav-label">{{ item.label }}</span>
            </button>
          </li>
          </ul>
        </nav>
      </div>
    </div>
  </aside>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import SidebarLink from './SidebarLink.vue'
import SidebarGroup from './SidebarGroup.vue'
import { useI18n } from '../i18n'

const props = defineProps({
  navItems: {
    type: Array,
    default: () => [],
  },
  currentUrl: {
    type: String,
    required: true,
  },
  sidebarOpen: {
    type: Boolean,
    default: false,
  },
  sidebarLocked: {
    type: Boolean,
    default: false,
  },
  isDesktop: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:sidebarLocked', 'update:sidebarOpen', 'logout', 'navigate'])

const { t } = useI18n()

const hovering = ref(false)

const STORAGE_OPEN = 'sb:openKey'

const loadStoredGroup = () => {
  if (typeof window === 'undefined') {
    return null
  }
  try {
    return localStorage.getItem(STORAGE_OPEN)
  } catch (error) {
    return null
  }
}

const openGroup = ref(loadStoredGroup())

const validGroupKeys = computed(() =>
  props.navItems.filter(item => item.type === 'group').map(item => item.key)
)

const activeGroupKey = computed(() => {
  const group = props.navItems.find(item => item.type === 'group' && isGroupActive(item))
  return group ? group.key : null
})

const toggleLock = () => {
  hovering.value = false
  emit('update:sidebarLocked', !props.sidebarLocked)
  if (!props.sidebarLocked) {
    emit('update:sidebarOpen', true)
  }
}

const handleMouseEnter = () => {
  if (!props.isDesktop || props.sidebarLocked) return
  hovering.value = true
  if (!props.sidebarOpen) {
    emit('update:sidebarOpen', true)
  }
}

const handleMouseLeave = () => {
  if (!props.isDesktop || props.sidebarLocked) return
  hovering.value = false
  if (props.sidebarOpen) {
    emit('update:sidebarOpen', false)
  }
}

const toggleGroup = key => {
  openGroup.value = openGroup.value === key ? null : key
}

const isGroupExpanded = key => openGroup.value === key

const isActive = item => {
  if (!item) return false
  if (typeof item.match === 'function') {
    return item.match(props.currentUrl)
  }
  if (item.exact) {
    return props.currentUrl === item.href
  }
  if (!item.href) return false
  return props.currentUrl.startsWith(item.href)
}

const isGroupActive = item => Array.isArray(item.children) && item.children.some(child => isActive(child))

const handleNavigate = href => {
  emit('navigate', href)

  if (!props.isDesktop) {
    emit('update:sidebarOpen', false)
    return
  }

  if (!props.sidebarLocked) {
    hovering.value = false
    emit('update:sidebarOpen', false)
  }
}

const resetSidebarState = ({ closeSidebar = true } = {}) => {
  hovering.value = false
  openGroup.value = null

  if (typeof window !== 'undefined') {
    try {
      localStorage.removeItem(STORAGE_OPEN)
    } catch (error) {}
  }

  if (closeSidebar) {
    emit('update:sidebarLocked', false)
    emit('update:sidebarOpen', false)
  }
}

const logout = () => {
  const shouldKeepVisible = props.isDesktop && props.sidebarLocked
  resetSidebarState({ closeSidebar: !shouldKeepVisible })
  emit('logout')
}

const navItemClass = item => ({
  'nav-item--group': item.type === 'group',
  'nav-item--active': item.type === 'link' && isActive(item),
  'nav-item--indicator': item.type === 'group' && item.indicator,
  'nav-item--collapsed': props.isDesktop && !props.sidebarOpen,
})

watch(activeGroupKey, key => {
  if (!openGroup.value && key) {
    openGroup.value = key
  }
})

watch(
  () => props.sidebarLocked,
  locked => {
    if (locked) {
      hovering.value = false
    }
  }
)

watch(
  () => props.isDesktop,
  isDesktop => {
    if (!isDesktop) {
      hovering.value = false
    }
  }
)

watch(
  () => props.sidebarOpen,
  open => {
    if (!open) {
      hovering.value = false
    }
  }
)

watch(
  () => props.navItems,
  () => {
    if (openGroup.value && !validGroupKeys.value.includes(openGroup.value)) {
      openGroup.value = null
    }
    if (!openGroup.value && activeGroupKey.value) {
      openGroup.value = activeGroupKey.value
    }
  },
  { deep: true }
)

watch(
  () => props.currentUrl,
  () => {
    if (activeGroupKey.value) {
      openGroup.value = activeGroupKey.value
    } else {
      openGroup.value = null
    }
  }
)

watch(openGroup, value => {
  if (typeof window === 'undefined') return
  try {
    if (value) {
      localStorage.setItem(STORAGE_OPEN, value)
    } else {
      localStorage.removeItem(STORAGE_OPEN)
    }
  } catch (error) {}
})

onMounted(() => {
  if (openGroup.value && !validGroupKeys.value.includes(openGroup.value)) {
    openGroup.value = null
  }
  if (!openGroup.value && activeGroupKey.value) {
    openGroup.value = activeGroupKey.value
  }
})
</script>

<style scoped>
.sidebar {
  position: fixed;
  top: var(--topbar-h, 72px);
  bottom: 0;
  left: 0;
  z-index: 45;
  width: var(--sb-max, 18rem);
  background: #ffffff;
  border-right: 1px solid rgba(226, 232, 240, 0.7);
  transition: transform 0.25s ease, width 0.25s ease;
  display: flex;
  flex-direction: column;
  box-shadow: 0 12px 32px -18px rgba(37, 99, 235, 0.2);
  --sidebar-active-bg: #2563eb;
  --sidebar-active-shadow-strong: 0 18px 42px -26px rgba(37, 99, 235, 0.65);
  --sidebar-active-shadow-medium: 0 18px 38px -24px rgba(37, 99, 235, 0.6);
  --sidebar-active-shadow-sub: 0 12px 30px -20px rgba(37, 99, 235, 0.5);
  --sidebar-pill-shadow: 0 10px 24px -18px rgba(37, 99, 235, 0.6);
}

.dark .sidebar {
  background: #0f172a;
  border-right-color: rgba(71, 85, 105, 0.6);
  box-shadow: 0 12px 32px -24px rgba(15, 23, 42, 0.9);
}

.sidebar--mobile {
  transform: translateX(-100%);
}

.sidebar--mobile.sidebar--open {
  transform: translateX(0);
}

.sidebar--desktop.sidebar--closed {
  width: var(--sb-mini, 4.75rem);
}

.sidebar__inner {
  display: flex;
  flex-direction: column;
  height: 100%;
  padding: 1rem 0.75rem 2rem;
  gap: 1rem;
  overflow: hidden;
}

.sidebar__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.sidebar--desktop.sidebar--closed .sidebar__header {
  justify-content: center;
}

.sidebar--desktop.sidebar--closed .hb-btn {
  margin: 0;
}

.sidebar__body {
  flex: 1 1 auto;
  overflow-y: auto;
  padding-right: 0.35rem;
  margin-right: -0.2rem;
  scroll-behavior: smooth;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: none;
}

.sidebar__body::-webkit-scrollbar {
  width: 0;
  height: 0;
}

.sidebar__title {
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #64748b;
}

.dark .sidebar__title {
  color: #e2e8f0;
}

.hb-btn {
  width: 2.35rem;
  height: 2.35rem;
  border-radius: 0.65rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: rgba(37, 99, 235, 0.12);
  color: #1d4ed8;
  transition: background 0.2s ease, color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
  border: none;
  cursor: pointer;
  position: relative;
  overflow: hidden;
  box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.18);
}

.hb-btn--locked {
  background: linear-gradient(135deg, #2563eb, #7c3aed);
  color: #fff;
  box-shadow: 0 14px 34px -22px rgba(79, 70, 229, 0.72);
}

.hb-btn--locked .hb-icon {
  color: #f8fafc;
}

.hb-btn:not(.hb-btn--locked):hover {
  background: rgba(37, 99, 235, 0.18);
  box-shadow: inset 0 0 0 1px rgba(37, 99, 235, 0.3);
  transform: translateY(-1px);
}

.dark .hb-btn:not(.hb-btn--locked) {
  background: rgba(37, 99, 235, 0.24);
  color: #dbeafe;
  box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.35);
}

.hb-icon {
  font-size: 1.35rem;
  line-height: 1;
  pointer-events: none;
}

.sidebar__menu {
  flex: 1 1 auto;
}

.sidebar__menu ul {
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
  list-style: none;
  padding: 0;
  margin: 0;
}

.nav-item {
  position: relative;
}

.nav-item--collapsed > .nav-link,
.nav-item--collapsed > .nav-btn {
  justify-content: center;
}

.nav-btn,
.nav-link {
  width: 100%;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.68rem 0.95rem;
  border-radius: 0.95rem;
  color: #0f172a;
  background: transparent;
  border: none;
  font-weight: 600;
  text-decoration: none;
  transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.2s ease;
}

.dark .nav-btn,
.dark .nav-link {
  color: #e2e8f0;
}

.nav-link:hover,
.nav-btn:hover {
  background: var(--sidebar-active-bg);
  color: #ffffff;
  box-shadow: var(--sidebar-active-shadow-medium);
}

.dark .nav-link:hover,
.dark .nav-btn:hover {
  background: var(--sidebar-active-bg);
  color: #e0f2ff;
}

.nav-item--active > .nav-link {
  background: var(--sidebar-active-bg);
  color: #fff;
  box-shadow: var(--sidebar-active-shadow-strong);
}

.nav-btn--active {
  background: var(--sidebar-active-bg);
  color: #ffffff;
  box-shadow: var(--sidebar-active-shadow-medium);
}

.nav-label {
  white-space: nowrap;
  font-size: 0.92rem;
}

.nav-btn--logout {
  color: #dc2626;
  background: rgba(239, 68, 68, 0.12);
  box-shadow: none;
}

.nav-btn--logout:hover {
  background: rgba(239, 68, 68, 0.2);
  color: #fff;
  box-shadow: 0 16px 30px -20px rgba(239, 68, 68, 0.5);
}

.nav-item--indicator > .nav-btn .material-icons,
.nav-item--indicator > .nav-link .material-icons {
  position: relative;
}

.nav-dot {
  width: 0.55rem;
  height: 0.55rem;
  border-radius: 999px;
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
  background: var(--sidebar-active-bg);
  color: #fff;
  box-shadow: var(--sidebar-pill-shadow);
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

.sidebar--desktop.sidebar--closed .nav-label,
.sidebar--desktop.sidebar--closed .nav-sub__badge,
.sidebar--desktop.sidebar--closed .nav-pill {
  display: none;
}

.sidebar--desktop.sidebar--closed .nav-dot {
  position: absolute;
  top: 0.45rem;
  right: 1rem;
}

@media (max-width: 1023px) {
  .sidebar {
    top: 0;
    width: 16rem;
  }
}
</style>
<style scoped>
/* Keep sidebar transitions even when OS requests reduced motion */
@media (prefers-reduced-motion: reduce) {
  .sidebar,
  .nav-item,
  .nav-btn,
  .nav-link,
  .sidebar__inner {
    transition-property: all !important;
    transition-duration: 0.25s !important;
    transition-timing-function: ease !important;
    animation-duration: 0.25s !important;
  }
}
</style>

