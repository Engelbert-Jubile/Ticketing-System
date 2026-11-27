<template>
  <Head :title="documentTitle" />
  <FlashNotifications :flash="page.props.flash" />
  <div class="app-shell" :class="{ 'app-shell--dark': theme === 'dark' }" :style="shellStyle">
    <LegacyTopbar
      :user="authUser"
      :notifications="notifications"
      :initial-search="searchQuery"
      :sidebar-open="sidebarOpen"
      :brand-url="portalUrl || resolveRouteName('dashboard')"
      :brand-label="t(portalUrl ? 'portal.title' : 'nav.dashboard')"
      :portal-mode="isPortal"
      :account-profile-url="resolveRouteName('account.profile')"
      :account-password-url="resolveRouteName('account.change-password')"
      :is-desktop="isDesktop"
      :theme="theme"
      :appearance="appearance"
      :theme-busy="themeBusy"
      :header-color="headerColor"
      :locale-busy="localeBusy"
      @update-locale="updateLocale"
      @toggle-sidebar="toggleSidebar"
      @toggle-theme="toggleTheme"
      @update-appearance="setAppearance"
      @update-header-color="updateHeaderColor"
      @search="performSearch"
      @mark-all-notifications="markAllNotifications"
      @mark-notification="markNotification"
      @delete-notification="deleteNotification"
      @logout="logout"
    />

    <LegacySidebar
      v-if="!isPortal"
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
      :class="{ 'with-sidebar': isDesktop && !isPortal }"
      :style="contentStyle"
    >
      <div v-if="!isPortal && !isDesktop && sidebarOpen" class="overlay" @click="setSidebarOpen(false)"></div>
      <main class="app-main">
        <p v-if="themeError" role="alert" class="mb-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950 dark:text-rose-200">{{ themeError }}</p>
        <p v-if="localeError" role="alert" class="mb-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800">{{ localeError }}</p>
        <p v-if="notificationError" role="alert" class="mb-4 rounded-xl bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-950 dark:text-rose-200">{{ notificationError }}</p>
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
              {{ $t("Tutup") }}
            </button>
          </div>
        </div>
        <nav
          v-if="activeWorkspaceTab"
          class="mx-auto mb-4 flex w-fit max-w-full flex-wrap justify-center gap-1 rounded-xl border border-slate-200 bg-white/80 p-1 shadow-sm dark:border-slate-700 dark:bg-slate-900/60"
          :aria-label="t(workspaces[workspaceOnPage].portal)"
        >
          <Link
            v-for="tab in workspaceTabs"
            :key="tab.key"
            :href="resolveRouteName(tab.route)"
            :aria-current="activeWorkspaceTab === tab.key ? 'page' : undefined"
            class="ticket-workspace-link inline-flex min-h-10 items-center gap-1.5 rounded-lg px-2.5 text-sm font-semibold transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-teal-600 sm:px-3 dark:focus-visible:outline-teal-300"
            :class="{ 'text-slate-700 dark:text-slate-300': activeWorkspaceTab !== tab.key }"
          >
            <span class="material-icons !text-[17px]" aria-hidden="true">{{ tab.icon }}</span>
            <span>{{ t(tab.label) }}</span>
            <span
              v-if="tab.key === 'in-progress' && workspaceBadge"
              class="ticket-workspace-badge rounded-full px-1.5 text-xs leading-5"
              :class="activeWorkspaceTab === tab.key
                ? 'bg-black/40'
                : 'bg-teal-100 text-teal-900 dark:bg-teal-400/15 dark:text-teal-200'"
            >{{ workspaceBadge }}</span>
          </Link>
        </nav>
        <slot />
      </main>
    </div>
  </div>
</template>

<script setup>
import { translateUi as uiText } from '../i18n'

import { Head, Link, router, usePage } from '@inertiajs/vue3'
import axios from 'axios'
import { computed, onBeforeUnmount, onMounted, provide, ref, watch } from 'vue'
import LegacySidebar from '../Components/LegacySidebar.vue'
import LegacyTopbar from '../Components/LegacyTopbar.vue'
import FlashNotifications from '../Components/FlashNotifications.vue'
import resolveRoute from '../utils/resolveRoute'
import { useI18n } from '../i18n'
import { applyThemePreferences, cacheThemePreferences, normalizeThemePreferences } from '../utils/appearance'

const page = usePage()
const { t, setLocale } = useI18n()
setLocale(page.props.locale || 'en')

const supportedLocales = ['en', 'id']
const activeLocale = computed(() => {
  const url = page.url ?? '/'
  const match = url.match(/^\/(en|id)\b/)
  return page.props.locale || (match ? match[1] : 'en')
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
const cloneNotifications = source => ({
  unread_count: Number(source?.unread_count ?? 0),
  items: Array.isArray(source?.items) ? source.items.map(item => ({ ...item })) : [],
})
const notificationError = ref('')
const notificationBusy = ref(false)
let notificationTimer = null
let notificationRevision = 0

const notificationsState = ref(cloneNotifications(page.props.notifications))
const notifications = computed(() => notificationsState.value)
const sidebarMetrics = computed(() => page.props.sidebarMetrics ?? {
  tickets_in_progress_count: 0,
  tasks_in_progress_count: 0,
  projects_in_progress_count: 0,
  can_manage_users: false,
})
const badgeValue = value => {
  if (typeof value !== 'number' || value <= 0) return undefined
  return value > 99 ? '99+' : String(value)
}

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
  return `${start || 'Now'} \u2014 ${end || uiText("Open")}`
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
const workspaces = {
  tickets: { label: 'nav.tickets', portal: 'nav.ticketPortal', create: 'Create Ticket', icon: 'confirmation_number' },
  tasks: { label: 'nav.tasks', portal: 'nav.taskPortal', create: 'nav.createTask', icon: 'task' },
  projects: { label: 'nav.projects', portal: 'nav.projectPortal', create: 'nav.createProject', icon: 'folder' },
}
const workspaceOnPage = computed(() => {
  if (page.component === 'ServerRoomTemperature') return null
  const [, area, module] = currentPath.value.split('/')
  if (Object.hasOwn(workspaces, area)) return area
  if (area === 'dashboard' && Object.hasOwn(workspaces, module)) return module
})
const workspaceTabs = computed(() => {
  const module = workspaceOnPage.value
  if (!module) return []

  const { create, icon } = workspaces[module]
  return [
    { key: 'create', label: create, icon, route: module + '.create' },
    { key: 'in-progress', label: 'nav.ticketsInProgress', icon: 'pending_actions', route: module + '.on-progress' },
    { key: 'report', label: 'nav.ticketsReport', icon: 'bar_chart', route: module + '.report' },
    { key: 'workflows', label: 'nav.workflows', icon: 'account_tree', route: module === 'tickets' ? 'workflows.index' : module + '.workflows.index' },
    { key: 'sla-reports', label: 'nav.slaReports', icon: 'schedule', route: module === 'tickets' ? 'dashboard.sla' : module + '.sla.index' },
  ]
})
const activeWorkspaceTab = computed(() => {
  if (!workspaceOnPage.value) return ''
  return workspaceTabs.value.find(tab => {
    const target = resolveRoute(tab.route, { locale: activeLocale.value }, false)
    if (!target.startsWith('/')) return false
    const path = stripLocalePrefix(target)
    return currentPath.value === path || currentPath.value.startsWith(path + '/')
  })?.key ?? 'report'
})
const workspaceBadge = computed(() => badgeValue(sidebarMetrics.value[workspaceOnPage.value + '_in_progress_count']))
const isPortal = computed(() => page.component === 'Portal')
const portalUrl = computed(() => page.props.portalUrl)
const sidebarWorkspace = ref(null)
const sidebarWorkspaceKey = userId => `sidebar:workspace:${userId}`

watch([() => authUser.value?.id, workspaceOnPage], ([userId, workspace], [previousUserId]) => {
  // Shared pages keep the workspace selected in this tab.
  if (workspace === undefined && userId === previousUserId) return

  sidebarWorkspace.value = userId ? workspace ?? null : null
  if (!userId) return

  try {
    const key = sidebarWorkspaceKey(userId)
    if (workspace === undefined) {
      const saved = sessionStorage.getItem(key)
      if (Object.hasOwn(workspaces, saved)) sidebarWorkspace.value = saved
    } else if (workspace) {
      sessionStorage.setItem(key, workspace)
    } else {
      sessionStorage.removeItem(key)
    }
  } catch (_) {}
}, { immediate: true })

const searchQuery = computed(() => {
  const url = inertiaUrl.value ?? ''
  const [, queryString] = url.split('?')
  if (!queryString) return ''
  const params = new URLSearchParams(queryString)
  return params.get('query') ?? ''
})

const sidebarOpen = ref(false)
const sidebarLocked = ref(false)
const initialTheme = normalizeThemePreferences(authUser.value?.theme_preferences)
const theme = ref(initialTheme.color_mode)
const appearance = ref(initialTheme.appearance)
const themeBusy = ref(false), themeError = ref('')
let savedTheme = initialTheme, themeSavePromise = null, themeMounted = true
const renderTheme = preferences => {
  const normalized = applyThemePreferences(preferences)
  theme.value = normalized.color_mode
  appearance.value = normalized.appearance
  if (window.tickoraTheme) window.tickoraTheme.preferences = normalized
}
renderTheme(initialTheme)
const syncAccountTheme = event => {
  if (themeBusy.value || String(event.detail?.user_id) !== String(authUser.value?.id)) return
  savedTheme = normalizeThemePreferences(event.detail.preferences)
  renderTheme(savedTheme)
}
const saveTheme = preferences => {
  if (themeBusy.value || !authUser.value?.id) return themeSavePromise
  const userId = authUser.value.id
  const next = normalizeThemePreferences(preferences)
  const previous = savedTheme
  const url = window.tickoraTheme?.save_url || resolveRouteName('account.theme.update')
  themeBusy.value = true
  themeError.value = ''
  renderTheme(next)
  themeSavePromise = (async () => {
    try {
      const { data } = await axios.patch(url, { user_id: userId, ...next })
      if (!themeMounted || authUser.value?.id !== userId) return
      savedTheme = normalizeThemePreferences(data.theme_preferences)
      renderTheme(savedTheme)
      cacheThemePreferences(userId, savedTheme)
    } catch (_) {
      if (!themeMounted || authUser.value?.id !== userId) return
      renderTheme(previous)
      themeError.value = uiText('Unable to save your theme. Please try again.')
    } finally {
      if (authUser.value?.id === userId) themeBusy.value = false
    }
  })()
  return themeSavePromise
}
const setAppearance = value => saveTheme({ appearance: value, color_mode: theme.value })
const toggleTheme = () => saveTheme({ appearance: appearance.value, color_mode: theme.value === 'dark' ? 'light' : 'dark' })
const headerColor = ref(authUser.value?.header_color ?? null)
const isDesktop = ref(false)
let headerColorSaveTimer = null
const shellStyle = computed(() => ({
  '--user-accent-color': headerColor.value || '#4338ca',
  '--app-header-background': headerColor.value
    ? 'linear-gradient(120deg, color-mix(in srgb, var(--user-accent-color) 88%, white) 0%, var(--user-accent-color) 48%, color-mix(in srgb, var(--user-accent-color) 82%, black) 100%)'
    : theme.value === 'dark'
      ? 'linear-gradient(120deg, rgba(15, 23, 42, 0.96) 0%, rgba(30, 41, 59, 0.92) 60%, rgba(30, 64, 175, 0.85) 100%)'
      : 'linear-gradient(120deg, #2563eb 0%, #4338ca 45%, #1d4ed8 100%)',
}))
provide('saveButtonBackground', computed(() => shellStyle.value['--app-header-background'].replaceAll('var(--user-accent-color)', shellStyle.value['--user-accent-color'])))

const SIDEBAR_LOGOUT_REASON_KEY = 'sidebar:logoutReason'
const mediaQuery = typeof window !== 'undefined' && window.matchMedia
  ? window.matchMedia('(min-width: 1024px)')
  : null

const resolveRouteName = (name, params = {}) => resolveRoute(name, { locale: activeLocale.value, ...params })

const pathStartsWith = (path, prefix) => path.startsWith(prefix)

const navItems = computed(() => {
  const metrics = sidebarMetrics.value ?? {}

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
      type: 'link', key: 'work', label: uiText("Pekerjaan Saya"), icon: 'assignment_ind',
      href: resolveRouteName('work.index'), match: path => path === '/my-work',
    },
    ...Object.entries(workspaces).map(([module, workspace]) => {
      const matches = () => workspaceOnPage.value === module
      return {
        type: 'group',
        key: module,
        label: t(workspace.label),
        icon: workspace.icon,
        indicator: metrics[module + '_in_progress_count'] > 0 ? { type: 'dot', variant: 'danger' } : null,
        children: [
          { key: module + '.create', label: t(workspace.portal), href: resolveRouteName(module + '.create'), match: matches },
        ],
        match: matches,
      }
    }),
  ]

  if (portalUrl.value) {
    items.unshift({
      type: 'link',
      key: 'portal',
      label: t('portal.title'),
      icon: 'apps',
      href: portalUrl.value,
      match: () => isPortal.value,
    })
  }

  const unit = String(authUser.value?.unit ?? '').trim()

  if (isSuperAdmin.value) {
    items.push({
      type: 'link',
      key: 'unit-reports',
      label: t('nav.unitReports'),
      icon: 'apartment',
      href: resolveRouteName('dashboard.unit-reports'),
      match: path => pathStartsWith(path, '/unit-reports'),
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
    if (unit !== '') {
      items.push({
        type: 'link',
        key: 'unit-reports',
        label: t('nav.unitReports'),
        icon: 'apartment',
        href: resolveRouteName('dashboard.unit-reports'),
        match: path => pathStartsWith(path, '/unit-reports'),
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
          match: path => path === '/users/create',
        },
        {
          key: 'users.report',
          label: t('nav.userList'),
          href: resolveRouteName('users.report'),
          match: path => path === '/users/report' || pathStartsWith(path, '/dashboard/users'),
        },
      ],
      match: path => pathStartsWith(path, '/users/') || pathStartsWith(path, '/dashboard/users'),
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
        match: path => path === '/account/profile',
      },
    ],
    match: path => pathStartsWith(path, '/account/') || pathStartsWith(path, '/dashboard/account'),
  })

  items.push(
    {
      type: 'link', key: 'knowledge', label: uiText("Panduan & Template"), icon: 'menu_book',
      href: resolveRouteName('knowledge.index'), match: path => path === '/guides-templates',
    },
    { type: 'logout', key: 'logout', label: t('nav.logout'), icon: 'logout' }
  )

  if (isLimitedUser.value) {
    items = items.filter(item => !['reports', 'settings'].includes(item.key))
  }

  if (sidebarWorkspace.value) {
    const order = ['portal', 'dashboard', sidebarWorkspace.value, 'work', 'unit-reports', 'users', 'account', 'knowledge', 'logout']
    return order.map(key => items.find(item => item.key === key)).filter(Boolean)
  }

  return items
})


const documentTitle = computed(() => {
  if (isPortal.value) return t('portal.title')

  const ticketTab = workspaceTabs.value.find(tab => tab.key === activeWorkspaceTab.value)
  if (ticketTab) return t(ticketTab.label)

  const path = currentPath.value

  const flatten = items => (items || []).flatMap(item => {
    if (item && Array.isArray(item.children)) return [item, ...flatten(item.children)]
    return [item]
  })

  const items = flatten(navItems.value)
  const matches = items.filter(item => typeof item?.match === 'function' && item.match(path))

  matches.sort((a, b) => String(b?.href || '').length - String(a?.href || '').length)

  const best = matches[0]
  return best?.label || t('nav.dashboard')
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

const localeBusy = ref(false), localeError = ref('')
const updateLocale = async value => {
  if (localeBusy.value || value === authUser.value?.preferred_locale) return
  localeBusy.value = true; localeError.value = ''
  try {
    const { data } = await axios.patch(resolveRouteName('account.locale.update'), { locale: value })
    setLocale(data.locale)
    const url = new URL(window.location.href)
    url.pathname = url.pathname.replace(/^\/(en|id)(?=\/|$)/, '/' + data.locale)
    router.visit(url.pathname + url.search + url.hash, { preserveScroll: true, preserveState: false, onFinish: () => { localeBusy.value = false } })
  } catch (error) { localeBusy.value = false; localeError.value = t('Unable to save your language. Please try again.') }
}

const updateHeaderColor = value => {
  const normalized = typeof value === 'string' && /^#[0-9a-fA-F]{6}$/.test(value)
    ? value.toLowerCase()
    : null

  headerColor.value = normalized

  if (headerColorSaveTimer !== null) {
    window.clearTimeout(headerColorSaveTimer)
  }

  headerColorSaveTimer = window.setTimeout(async () => {
    try {
      await axios.patch(resolveRouteName('account.header-color.update'), {
        header_color: headerColor.value,
      })
    } catch (error) {}
  }, 400)
}

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

const syncNotifications = page => {
  notificationRevision++
  const source = page?.props?.notifications ?? page.props.notifications
  notificationsState.value = cloneNotifications(source)
}
const notificationVisit = async (method, url, applyLocalChange) => {
  if (notificationBusy.value) return false
  notificationBusy.value = true
  notificationRevision++
  notificationError.value = ''
  const previous = cloneNotifications(notificationsState.value)

  if (typeof applyLocalChange === 'function') {
    applyLocalChange()
  }

  if (!url || url === '#') {
    notificationsState.value = previous
    notificationBusy.value = false
    return false
  }

  try {
    const response = method === 'delete'
      ? await axios.delete(url)
      : await axios.post(url)

    if (response?.data?.notifications) {
      notificationsState.value = cloneNotifications(response.data.notifications)
      notificationBusy.value = false
      return true
    }
  } catch (error) {
  }

  notificationsState.value = previous
  notificationBusy.value = false
  notificationError.value = uiText("Notifikasi belum tersimpan. Silakan coba lagi.")
  return false
}
const markAllNotifications = () => notificationVisit('post', resolveRouteName('notifications.read-all'), () => {
  notificationsState.value = {
    unread_count: 0,
    items: notificationsState.value.items.map(item => ({ ...item, read_at: item.read_at || new Date().toISOString() })),
  }
})

const markNotification = id => notificationVisit('post', resolveRouteName('notifications.mark', { id }), () => {
  const item = notificationsState.value.items.find(item => String(item.id) === String(id))
  if (item && !item.read_at) {
    item.read_at = new Date().toISOString()
    notificationsState.value.unread_count = Math.max(0, notificationsState.value.unread_count - 1)
  }
})

const refreshNotifications = async () => {
  if (document.hidden || notificationBusy.value) return
  const revision = ++notificationRevision
  try {
    const { data } = await axios.get(resolveRouteName('notifications.index'))
    if (!notificationBusy.value && revision === notificationRevision) notificationsState.value = cloneNotifications(data.notifications)
  } catch (_) { /* Keep current notifications during a temporary connection failure. */ }
}

const deleteNotification = id => {
  void notificationVisit('delete', resolveRouteName('notifications.destroy', { id }), () => {
    const target = notificationsState.value.items.find(item => item.id === id)
    const unreadCount = target && !target.read_at
      ? Math.max(0, notificationsState.value.unread_count - 1)
      : notificationsState.value.unread_count

    notificationsState.value = {
      unread_count: unreadCount,
      items: notificationsState.value.items.filter(item => item.id !== id),
    }
  })
}

const stopImpersonation = () => {
  router.post(resolveRouteName('settings.impersonate.stop'), {}, { preserveScroll: true })
}

const logout = async () => {
  await themeSavePromise
  try {
    localStorage.setItem(SIDEBAR_LOGOUT_REASON_KEY, 'manual')
    sessionStorage.removeItem(sidebarWorkspaceKey(authUser.value?.id))
  } catch (error) {}
  resetSidebarPersistence()
  router.post(resolveRouteName('logout'))
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

  if (sidebarLocked.value) {
    return '18rem'
  }

  return '4.75rem'
})

const contentStyle = computed(() => {
  if (isPortal.value || !isDesktop.value) {
    return { marginLeft: '0px' }
  }
  return { marginLeft: sidebarWidth.value }
})

onMounted(() => {
  window.addEventListener('tickora:theme-updated', syncAccountTheme)
  notificationTimer = window.setInterval(refreshNotifications, 30000)
  window.addEventListener('focus', refreshNotifications)
  loadSidebarState()
  initBreakpoint()
  if (isDesktop.value) {
    sidebarOpen.value = sidebarLocked.value
  }
  syncAnnouncement()
})

onBeforeUnmount(() => {
  themeMounted = false
  window.removeEventListener('tickora:theme-updated', syncAccountTheme)
  window.clearInterval(notificationTimer)
  window.removeEventListener('focus', refreshNotifications)
  cleanupBreakpoint()
  if (headerColorSaveTimer !== null) {
    window.clearTimeout(headerColorSaveTimer)
  }
})

watch([announcementKey, announcement], () => {
  syncAnnouncement()
})

watch(isPortal, portal => {
  if (portal && !isDesktop.value && sidebarOpen.value) setSidebarOpen(false)
})

watch(() => authUser.value?.id, () => {
  savedTheme = normalizeThemePreferences(authUser.value?.theme_preferences)
  themeBusy.value = false
  themeError.value = ''
  themeSavePromise = null
  if (window.tickoraTheme) {
    window.tickoraTheme.user_id = authUser.value?.id ?? null
    window.tickoraTheme.save_url = resolveRouteName('account.theme.update')
  }
  headerColor.value = authUser.value?.header_color ?? null
  renderTheme(savedTheme)
})


watch(
  () => page.props.notifications,
  value => {
    notificationsState.value = cloneNotifications(value)
  },
  { deep: true }
)

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
  min-height: 100dvh;
  background: #f1f5f9;
  color: #0f172a;
  overflow-x: clip;
}

.app-shell--dark {
  background: #0f172a;
  color: #e2e8f0;
}

.app-content {
  padding-top: var(--topbar-h, 72px);
  transition: margin-left 0.25s ease;
  min-width: 0;
  overflow-x: clip;
}

.app-main {
  min-height: calc(100vh - var(--topbar-h, 72px));
  min-height: calc(100dvh - var(--topbar-h, 72px));
  padding: 1.5rem;
  max-width: 100%;
  overflow-x: clip;
}

.ticket-workspace-link:is([aria-current='page'], :hover, :focus-visible) {
  background-color: var(--user-accent-color);
}

.ticket-workspace-link[aria-current='page'],
.ticket-workspace-link[aria-current='page'] > span,
.ticket-workspace-link:is(:hover, :focus-visible) > span:not(.ticket-workspace-badge) {
  color: #fff;
}

.overlay {
  position: fixed;
  inset: var(--topbar-h, 72px) 0 0;
  background: rgba(15, 23, 42, 0.45);
  z-index: 30;
}

@media (max-width: 1023px) {
  .app-content {
    transition: none;
  }

  .app-main {
    padding: 1rem 0.875rem 1.25rem;
  }
}
</style>







