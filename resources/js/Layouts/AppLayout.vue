<template>
  <div class="app-shell" :class="{ 'app-shell--dark': theme === 'dark' }">
    <LegacyTopbar
      :user="authUser"
      :notifications="notifications"
      :initial-search="searchQuery"
      :sidebar-open="sidebarOpen"
      :dashboard-url="resolveRouteName('dashboard')"
      :account-profile-url="resolveRouteName('account.profile')"
      :account-password-url="resolveRouteName('account.change-password')"
      :is-desktop="isDesktop"
      :theme="theme"
      @toggle-sidebar="toggleSidebar"
      @toggle-theme="toggleTheme"
      @search="performSearch"
      @mark-all-notifications="markAllNotifications"
      @mark-notification="markNotification"
      @delete-notification="deleteNotification"
      @logout="logout"
    />

    <LegacySidebar
      :nav-items="navItems"
      :current-url="currentPath"
      :sidebar-open="sidebarOpen"
      :sidebar-locked="sidebarLocked"
      :is-desktop="isDesktop"
      @update:sidebarLocked="setSidebarLocked"
      @update:sidebarOpen="setSidebarOpen"
      @logout="logout"
      @navigate="navigate"
    />

    <div
      class="app-content"
      :class="{ 'with-sidebar': isDesktop }"
      :style="contentStyle"
    >
      <div v-if="!isDesktop && sidebarOpen" class="overlay" @click="setSidebarOpen(false)"></div>
      <main class="app-main">
        <div
          v-if="impersonationActive"
          class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-500/40 dark:bg-amber-900/30 dark:text-amber-100"
        >
          <span>{{ t('layout.impersonating') }}</span>
          <button type="button" class="rounded-full border border-amber-300 px-3 py-1 text-xs font-semibold" @click="stopImpersonation">
            {{ t('layout.stopImpersonation') }}
          </button>
        </div>
        <div
          v-if="announcement && showAnnouncement"
          class="mb-4 rounded-xl border border-slate-200 bg-gradient-to-r from-amber-50 via-white to-amber-50 px-4 py-3 shadow-sm dark:border-amber-500/30 dark:from-amber-900/20 dark:via-slate-900 dark:to-amber-900/15"
        >
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="flex items-start gap-3">
              <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-700 shadow-sm ring-1 ring-amber-200/60 dark:bg-amber-800/60 dark:text-amber-50 dark:ring-amber-500/50">
                <span class="material-icons text-base">campaign</span>
              </div>
              <div class="space-y-1">
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-50">{{ announcement.title || 'Announcement' }}</p>
                <p v-if="announcement.body" class="text-sm leading-relaxed text-amber-800/90 dark:text-amber-100/90">
                  {{ announcement.body }}
                </p>
                <p v-if="displayWindow" class="text-xs font-medium text-amber-700/90 dark:text-amber-200/90">
                  {{ displayWindow }}
                </p>
              </div>
            </div>
            <button
              type="button"
              class="ml-auto text-xs font-semibold text-amber-800 underline underline-offset-4 transition hover:text-amber-900 dark:text-amber-100 dark:hover:text-white"
              @click="dismissAnnouncement"
            >
              Tutup
            </button>
          </div>
        </div>
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { router, usePage } from '@inertiajs/vue3'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import LegacySidebar from '../Components/LegacySidebar.vue'
import LegacyTopbar from '../Components/LegacyTopbar.vue'
import resolveRoute from '../utils/resolveRoute'
import { useI18n } from '../i18n'

const page = usePage()
const { t, setLocale } = useI18n()
setLocale(page.props.locale || 'en')

const supportedLocales = ['en', 'id']
const activeLocale = computed(() => {
  const url = page.url ?? '/'
  const match = url.match(/^\/(en|id)\b/)
  return match ? match[1] : (page.props.locale || 'en')
})

const inertiaUrl = computed(() => page.url ?? '/')

const authUser = computed(() => page.props.auth?.user ?? null)
const userRoles = computed(() => (authUser.value?.roles ?? []).map(role => String(role).toLowerCase()))
const isSuperAdmin = computed(() => userRoles.value.includes('superadmin'))
const impersonation = computed(() => page.props.impersonation ?? { active: false })
const impersonationActive = computed(() => Boolean(impersonation.value?.active))
const isLimitedUser = computed(() => {
  const roles = userRoles.value
  if (!roles.length) return false
  const hasAdmin = roles.includes('admin') || roles.includes('superadmin')
  if (hasAdmin) return false
  return roles.includes('user')
})
const notifications = computed(() => page.props.notifications ?? { unread_count: 0, items: [] })
const sidebarMetrics = computed(() => page.props.sidebarMetrics ?? {
  tickets_in_progress_count: 0,
  tasks_in_progress_count: 0,
  projects_in_progress_count: 0,
  can_manage_users: false,
})

const announcement = computed(() => {
  const raw = page.props.announcement ?? null
  if (!raw || !raw.enabled) return null

  return {
    ...raw,
    body: raw.body ?? raw.message ?? null,
    start_at: raw.start_at ?? raw.starts_at ?? null,
    end_at: raw.end_at ?? raw.ends_at ?? null,
    starts_at: raw.start_at ?? raw.starts_at ?? null,
    ends_at: raw.end_at ?? raw.ends_at ?? null,
  }
})
const announcementKey = computed(() => {
  const a = announcement.value
  if (!a) return ''
  return [a.title ?? '', a.body ?? '', a.start_at ?? a.starts_at ?? '', a.end_at ?? a.ends_at ?? ''].join('|')
})
const displayWindow = computed(() => {
  if (!announcement.value) return ''
  const start = announcement.value.start_at || announcement.value.starts_at
  const end = announcement.value.end_at || announcement.value.ends_at
  if (!start && !end) return ''
  return `${start || 'Now'} \u2014 ${end || 'Open'}`
})
const showAnnouncement = ref(Boolean(announcement.value))

const stripLocalePrefix = path => {
  if (typeof path !== 'string' || path.trim() === '') {
    return '/'
  }
  const clean = path.startsWith('/') ? path : `/${path}`
  const segments = clean.split('/').filter(Boolean)
  if (segments.length && supportedLocales.includes(segments[0])) {
    const remaining = segments.slice(1).join('/')
    return remaining ? `/${remaining}` : '/'
  }
  return clean
}

const currentPath = computed(() => {
  const url = inertiaUrl.value ?? '/'
  return stripLocalePrefix(url.split('?')[0] || '/')
})

const searchQuery = computed(() => {
  const url = inertiaUrl.value ?? ''
  const [, queryString] = url.split('?')
  if (!queryString) return ''
  const params = new URLSearchParams(queryString)
  return params.get('query') ?? ''
})

const sidebarOpen = ref(false)
const sidebarLocked = ref(false)
const theme = ref('light')
const isDesktop = ref(false)

const SIDEBAR_LOGOUT_REASON_KEY = 'sidebar:logoutReason'
const mediaQuery = typeof window !== 'undefined' && window.matchMedia
  ? window.matchMedia('(min-width: 1024px)')
  : null

const resolveRouteName = (name, params = {}) => resolveRoute(name, { locale: activeLocale.value, ...params })

const pathStartsWith = (path, prefix) => path.startsWith(prefix)

const navItems = computed(() => {
  const metrics = sidebarMetrics.value ?? {}
  const badgeValue = value => {
    if (typeof value !== 'number' || value <= 0) return undefined
    return value > 99 ? '99+' : String(value)
  }

  let items = [
    {
      type: 'link',
      key: 'dashboard',
      label: t('nav.dashboard'),
      icon: 'dashboard',
      href: resolveRouteName('dashboard'),
      match: path => path === '/dashboard',
    },
    {
      type: 'group',
      key: 'tickets',
      label: t('nav.tickets'),
      icon: 'confirmation_number',
      indicator: metrics.tickets_in_progress_count > 0 ? { type: 'dot', variant: 'danger' } : null,
      children: [
        {
          key: 'tickets.create',
          label: t('nav.createTicket'),
          href: resolveRouteName('tickets.create'),
          match: path => path === '/dashboard/tickets/create',
        },
        {
          key: 'tickets.on-progress',
          label: t('nav.ticketsInProgress'),
          href: resolveRouteName('tickets.on-progress'),
          badge: badgeValue(metrics.tickets_in_progress_count),
          match: path => path === '/dashboard/tickets/on-progress',
        },
        {
          key: 'tickets.report',
          label: t('nav.ticketsReport'),
          href: resolveRouteName('tickets.report'),
          match: path => pathStartsWith(path, '/dashboard/tickets') && !path.includes('/create') && !path.includes('/on-progress'),
        },
      ],
      match: path => pathStartsWith(path, '/dashboard/tickets'),
    },
    {
      type: 'group',
      key: 'tasks',
      label: t('nav.tasks'),
      icon: 'task',
      indicator: metrics.tasks_in_progress_count > 0 ? { type: 'dot', variant: 'danger' } : null,
      children: [
        {
          key: 'tasks.create',
          label: t('nav.createTask'),
          href: resolveRouteName('tasks.create'),
          match: path => path === '/dashboard/tasks/create',
        },
        {
          key: 'tasks.on-progress',
          label: t('nav.tasksInProgress'),
          href: resolveRouteName('tasks.on-progress'),
          badge: badgeValue(metrics.tasks_in_progress_count),
          match: path => path === '/dashboard/tasks/on-progress',
        },
        {
          key: 'tasks.report',
          label: t('nav.tasksReport'),
          href: resolveRouteName('tasks.report'),
          match: path => pathStartsWith(path, '/dashboard/tasks') && !path.includes('/create') && !path.includes('/on-progress'),
        },
      ],
      match: path => pathStartsWith(path, '/dashboard/tasks'),
    },
    {
      type: 'group',
      key: 'projects',
      label: t('nav.projects'),
      icon: 'folder',
      indicator: metrics.projects_in_progress_count > 0 ? { type: 'dot', variant: 'danger' } : null,
      children: [
        {
          key: 'projects.create',
          label: t('nav.createProject'),
          href: resolveRouteName('projects.create'),
          match: path => path === '/dashboard/projects/create',
        },
        {
          key: 'projects.on-progress',
          label: t('nav.projectsInProgress'),
          href: resolveRouteName('projects.on-progress'),
          badge: badgeValue(metrics.projects_in_progress_count),
          match: path => path === '/dashboard/projects/on-progress',
        },
        {
          key: 'projects.report',
          label: t('nav.projectsReport'),
          href: resolveRouteName('projects.report'),
          match: path => pathStartsWith(path, '/dashboard/projects') && !path.includes('/create') && !path.includes('/on-progress'),
        },
      ],
      match: path => pathStartsWith(path, '/dashboard/projects'),
    },
  ]

  const unit = String(authUser.value?.unit ?? '').trim()

  if (isSuperAdmin.value) {
    items.push({
      type: 'link',
      key: 'sla',
      label: t('nav.slaReports'),
      icon: 'schedule',
      href: resolveRouteName('dashboard.sla'),
      match: path => pathStartsWith(path, '/dashboard/sla'),
    })

    items.push({
      type: 'link',
      key: 'unit-reports',
      label: t('nav.unitReports'),
      icon: 'apartment',
      href: resolveRouteName('dashboard.unit-reports'),
      match: path => pathStartsWith(path, '/dashboard/unit-reports'),
    })

    items.push({
      type: 'link',
      key: 'reports',
      label: t('nav.performanceReports'),
      icon: 'analytics',
      href: resolveRouteName('reports.index'),
      match: path => pathStartsWith(path, '/dashboard/reports'),
    })

    items.push({
      type: 'link',
      key: 'settings',
      label: t('nav.settings'),
      icon: 'settings_suggest',
      href: resolveRouteName('settings'),
      match: path => pathStartsWith(path, '/dashboard/settings'),
    })
  } else {
    items.push({
      type: 'link',
      key: 'sla',
      label: t('nav.slaReports'),
      icon: 'schedule',
      href: resolveRouteName('dashboard.sla'),
      match: path => pathStartsWith(path, '/dashboard/sla'),
    })

    if (unit !== '') {
      items.push({
        type: 'link',
        key: 'unit-reports',
        label: t('nav.unitReports'),
        icon: 'apartment',
        href: resolveRouteName('dashboard.unit-reports'),
        match: path => pathStartsWith(path, '/dashboard/unit-reports'),
      })
    }
  }

  if (metrics.can_manage_users) {
    items.push({
      type: 'group',
      key: 'users',
      label: t('nav.users'),
      icon: 'people',
      children: [
        {
          key: 'users.create',
          label: t('nav.createUser'),
          href: resolveRouteName('users.create'),
          match: path => path === '/dashboard/users/create',
        },
        {
          key: 'users.report',
          label: t('nav.userList'),
          href: resolveRouteName('users.report'),
          match: path => pathStartsWith(path, '/dashboard/users') && !path.includes('/create'),
        },
      ],
      match: path => pathStartsWith(path, '/dashboard/users'),
    })
  }

  items.push({
    type: 'group',
    key: 'account',
    label: t('nav.account'),
    icon: 'manage_accounts',
    children: [
      {
        key: 'account.profile',
        label: t('nav.profile'),
        href: resolveRouteName('account.profile'),
        match: path => path === '/dashboard/account/profile',
      },
      {
        key: 'account.change-password',
        label: t('nav.changePassword'),
        href: resolveRouteName('account.change-password'),
        match: path => path === '/dashboard/account/change-password',
      },
    ],
    match: path => pathStartsWith(path, '/dashboard/account'),
  })

  items.push(
    { type: 'logout', key: 'logout', label: t('nav.logout'), icon: 'logout' }
  )

  if (isLimitedUser.value) {
    items = items.filter(item => !['reports', 'settings'].includes(item.key))
  }

  return items
})

const setSidebarOpen = value => {
  sidebarOpen.value = value
  if (!isDesktop.value || sidebarLocked.value) {
    persistSidebarState()
  }
}

const setSidebarLocked = value => {
  sidebarLocked.value = value
  if (isDesktop.value) {
    sidebarOpen.value = value
  }
  persistSidebarState()
}

const toggleSidebar = () => {
  if (isDesktop.value) {
    setSidebarLocked(!sidebarLocked.value)
  } else {
    setSidebarOpen(!sidebarOpen.value)
  }
}

const persistSidebarState = () => {
  try {
    localStorage.setItem('sidebarLocked', String(sidebarLocked.value))
    localStorage.setItem('sidebarOpen', String(sidebarOpen.value))
  } catch (error) {}
}

const resetSidebarPersistence = () => {
  try {
    localStorage.setItem('sb:forceClosed', '1')
    localStorage.removeItem('sidebarLocked')
    localStorage.removeItem('sidebarOpen')
    localStorage.removeItem('sb:openKey')
    localStorage.removeItem('sb:selected')
  } catch (error) {}
}

const loadSidebarState = () => {
  try {
    const logoutReason = localStorage.getItem(SIDEBAR_LOGOUT_REASON_KEY)
    if (logoutReason === 'manual') {
      localStorage.removeItem(SIDEBAR_LOGOUT_REASON_KEY)
      resetSidebarPersistence()
      sidebarLocked.value = false
      sidebarOpen.value = false
      return
    }

    if (localStorage.getItem('sb:forceClosed') === '1') {
      localStorage.removeItem('sb:forceClosed')
      sidebarLocked.value = false
      sidebarOpen.value = false
      return
    }

    const locked = localStorage.getItem('sidebarLocked')
    const open = localStorage.getItem('sidebarOpen')
    sidebarLocked.value = locked === 'true'
    sidebarOpen.value = open === 'true'
  } catch (error) {
    sidebarLocked.value = false
    sidebarOpen.value = false
  }
}

const applyTheme = value => {
  if (typeof document !== 'undefined') {
    document.documentElement.classList.toggle('dark', value === 'dark')
    document.documentElement.classList.toggle('light', value === 'light')
  }
}

const loadTheme = () => {
  try {
    const stored = localStorage.getItem('theme')
    if (stored === 'dark' || stored === 'light') {
      theme.value = stored
    }
  } catch (error) {}
  applyTheme(theme.value)
}

const toggleTheme = () => {
  theme.value = theme.value === 'dark' ? 'light' : 'dark'
}

watch(theme, value => {
  try {
    localStorage.setItem('theme', value)
  } catch (error) {}
  applyTheme(value)
})

const handleBreakpoint = event => {
  isDesktop.value = event.matches
  if (isDesktop.value) {
    sidebarOpen.value = sidebarLocked.value
  } else {
    sidebarOpen.value = false
  }
  persistSidebarState()
}

const initBreakpoint = () => {
  if (!mediaQuery) return
  isDesktop.value = mediaQuery.matches
  sidebarOpen.value = isDesktop.value ? sidebarLocked.value : false
  mediaQuery.addEventListener ? mediaQuery.addEventListener('change', handleBreakpoint) : mediaQuery.addListener(handleBreakpoint)
}

const cleanupBreakpoint = () => {
  if (!mediaQuery) return
  mediaQuery.removeEventListener ? mediaQuery.removeEventListener('change', handleBreakpoint) : mediaQuery.removeListener(handleBreakpoint)
}

const performSearch = query => {
  const params = query ? { query } : {}
  router.get(resolveRouteName('search'), params, { preserveState: true })
}

const markAllNotifications = () => {
  router.post(resolveRouteName('notifications.read-all'), {}, { preserveScroll: true })
}

const markNotification = id => {
  router.post(resolveRouteName('notifications.mark', { id }), {}, { preserveScroll: true })
}

const deleteNotification = id => {
  router.delete(resolveRouteName('notifications.destroy', { id }), { preserveScroll: true })
}

const stopImpersonation = () => {
  router.post(resolveRouteName('settings.impersonate.stop'), {}, { preserveScroll: true })
}

const logout = () => {
  try {
    localStorage.setItem(SIDEBAR_LOGOUT_REASON_KEY, 'manual')
  } catch (error) {}
  resetSidebarPersistence()
  window.location.href = resolveRouteName('logout.get')
}

const navigate = href => {
  router.visit(href)
}

const syncAnnouncement = () => {
  try {
    if (!announcement.value) {
      showAnnouncement.value = false
      return
    }
    if (!announcementKey.value) {
      showAnnouncement.value = true
      return
    }
    const dismissedKey = localStorage.getItem('announcement:dismissed') || ''
    showAnnouncement.value = dismissedKey !== announcementKey.value
  } catch (error) {
    showAnnouncement.value = Boolean(announcement.value)
  }
}

const dismissAnnouncement = () => {
  try {
    if (announcementKey.value) {
      localStorage.setItem('announcement:dismissed', announcementKey.value)
    }
  } catch (error) {}
  showAnnouncement.value = false
}

const sidebarWidth = computed(() => {
  if (!isDesktop.value) {
    return '0px'
  }

  if (sidebarLocked.value || sidebarOpen.value) {
    return '18rem'
  }

  return '4.75rem'
})

const contentStyle = computed(() => {
  if (!isDesktop.value) {
    return { marginLeft: '0px' }
  }
  return { marginLeft: sidebarWidth.value }
})

onMounted(() => {
  loadSidebarState()
  loadTheme()
  initBreakpoint()
  if (isDesktop.value) {
    sidebarOpen.value = sidebarLocked.value
  }
  syncAnnouncement()
})

onBeforeUnmount(() => {
  cleanupBreakpoint()
})

watch([announcementKey, announcement], () => {
  syncAnnouncement()
})

watch(
  () => page.props.locale,
  value => {
    if (value) setLocale(value)
  }
)
</script>

<style scoped>
.app-shell {
  min-height: 100vh;
  background: #f1f5f9;
  color: #0f172a;
}

.app-shell--dark {
  background: #0f172a;
  color: #e2e8f0;
}

.app-content {
  padding-top: var(--topbar-h, 72px);
  transition: margin-left 0.25s ease;
}

.app-main {
  min-height: calc(100vh - var(--topbar-h, 72px));
  padding: 1.5rem;
}

.overlay {
  position: fixed;
  inset: var(--topbar-h, 72px) 0 0;
  background: rgba(15, 23, 42, 0.45);
  z-index: 30;
}
</style>
