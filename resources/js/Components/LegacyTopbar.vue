<template>
  <nav ref="topbarRef" class="topbar" role="banner">
    <div
      class="topbar__inner mx-auto flex h-auto min-h-16 w-full flex-wrap items-center gap-3 px-4 md:grid md:grid-cols-[auto,minmax(0,1fr),auto] md:items-center md:gap-4 md:px-6 lg:gap-5 lg:px-8">
      <div class="topbar__left order-1 flex items-center gap-3 min-w-0">
        <button
          v-if="!isDesktop"
          type="button"
          class="topbar__mobile-menu-btn topbar-icon-btn sb-ripple"
          :aria-label="sidebarOpen ? t('topbar.menuClose') : t('topbar.menuOpen')"
          :aria-pressed="String(sidebarOpen)"
          @click="emitToggleSidebar"
        >
          <span class="material-icons text-[22px]">menu</span>
        </button>

        <Link :href="dashboardUrl" class="topbar__brand">
          <span class="topbar__logo" aria-hidden="true">
            <img
              src="https://img.icons8.com/?size=256&id=5LdqaP8dgiOs&format=png"
              alt="Ikon Ticket Management System"
              class="topbar__logo-img"
              loading="lazy"
              decoding="async"
            />
          </span>
          <span class="topbar__brand-text">TICKORA</span>
        </Link>
      </div>

      <div class="topbar__right order-2 flex min-w-0 flex-1 flex-wrap items-center justify-end gap-2 md:order-3 md:flex-nowrap md:gap-3">
        <div class="topbar__welcome">
          <span class="welcome">{{ t('topbar.welcome') }}</span>
          <span class="name">{{ displayName }}</span>
        </div>

        <div class="topbar__actions">
          <div class="relative" ref="notificationsRef">
            <button
              type="button"
              class="header-action topbar-icon-btn"
              :class="{ 'is-active': notificationsOpen }"
              :aria-expanded="String(notificationsOpen)"
              :aria-label="`${t('topbar.notifications')} (${badgeAria})`"
              :title="`${t('topbar.notifications')} (${badgeAria})`"
              @click="toggleNotifications"
            >
              <i class="material-icons">notifications</i>
              <span v-if="unreadBadge" class="topbar__badge">{{ unreadBadge }}</span>
            </button>

            <transition name="fade">
              <div v-if="notificationsOpen" class="dropdown-backdrop" @click="closeNotifications"></div>
            </transition>

            <transition name="fade">
              <div
                v-if="notificationsOpen"
                class="topbar-dropdown topbar-dropdown--notifications overflow-hidden rounded-2xl bg-white text-slate-800 shadow-none ring-1 ring-slate-200 z-[100000] dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700"
              >
                <div class="dropdown-header">
                  <span>{{ t('topbar.notifications') }}</span>
                  <button
                    v-if="unreadCount > 0"
                    type="button"
                    class="dropdown-header__action"
                    @click="markAllNotifications"
                  >
                    {{ t('topbar.markAll') }}
                  </button>
                </div>

                <div v-if="!notificationItems.length" class="dropdown-empty">
                  {{ t('topbar.noNotifications') }}
                </div>

                <ul v-else class="dropdown-list modern">
                  <li
                    v-for="item in notificationItems"
                    :key="item.id"
                    class="notif-card"
                    :class="{ 'notif-card--unread': !item.read_at }"
                  >
                    <div class="notif-icon">
                      <span class="material-icons">{{ item.icon || 'notifications' }}</span>
                    </div>
                    <div class="notif-body">
                      <div class="notif-head">
                        <span class="notif-title">{{ item.title }}</span>
                        <span v-if="!item.read_at" class="notif-dot"></span>
                      </div>
                      <p class="notif-message">{{ item.message }}</p>
                      <div class="notif-meta">
                        <span class="notif-time">{{ item.time_ago }}</span>
                        <Link
                          v-if="item.url"
                          :href="item.url"
                          class="notif-link"
                          @click="closeNotifications"
                        >
                          {{ t('topbar.open') }}
                        </Link>
                      </div>
                    </div>
                    <div class="notif-actions">
                      <button
                        type="button"
                        class="notif-btn"
                        @click="markNotification(item.id)"
                        aria-label="Tandai sudah dibaca"
                      >
                        <span class="material-icons">check</span>
                      </button>
                      <button
                        type="button"
                        class="notif-btn notif-btn--danger"
                        @click="deleteNotification(item.id)"
                        aria-label="Hapus notifikasi"
                      >
                        <span class="material-icons">close</span>
                      </button>
                    </div>
                  </li>
                </ul>
              </div>
            </transition>
          </div>

          <div class="relative" ref="accountRef">
            <button
              type="button"
              class="topbar-icon-btn topbar-user-btn sb-ripple"
              :class="{ 'is-active': accountOpen }"
              :aria-expanded="String(accountOpen)"
              :aria-label="t('topbar.accountSettings')"
              :title="t('topbar.accountSettings')"
              @click="toggleAccount"
            >
              <span class="material-icons text-[22px] text-white">account_circle</span>
            </button>

            <transition name="fade">
              <div v-if="accountOpen" class="dropdown-backdrop" @click="closeAccount"></div>
            </transition>

            <transition name="fade">
              <div
                v-if="accountOpen"
                class="topbar-dropdown topbar-dropdown--account overflow-hidden rounded-2xl bg-white text-slate-800 shadow-none ring-1 ring-slate-200 z-[100000] dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-700"
              >
                <div class="dropdown-account__header">
                  <span class="dropdown-account__heading">{{ t('topbar.account') }}</span>
                </div>
                <div class="dropdown-account__body">
                  <Link :href="accountProfileUrl" class="dropdown-account__link" @click="closeAccount">{{ t('topbar.profile') }}</Link>
                  <Link :href="accountPasswordUrl" class="dropdown-account__link" @click="closeAccount">{{ t('topbar.changePassword') }}</Link>
                </div>
                <div class="dropdown-account__footer">
                  <button type="button" class="dropdown-account__logout" @click="logout">{{ t('topbar.logout') }}</button>
                </div>
              </div>
            </transition>
          </div>

          <button
            type="button"
            class="topbar-icon-btn"
            :title="themeToggleTitle"
            :aria-label="themeToggleTitle"
            @click="emitToggleTheme"
          >
            <i class="material-icons">{{ themeIcon }}</i>
          </button>
        </div>
      </div>

      <div class="topbar__search order-3 flex w-full flex-1 min-w-0 justify-center md:order-2 md:w-auto" role="search">
        <form class="topbar-search max-w-md md:max-w-xl lg:max-w-2xl" role="search" @submit.prevent="submitSearch">
          <span class="material-icons topbar-search__icon">search</span>
          <input
            v-model.trim="search"
            class="topbar-search__input"
            type="search"
            :placeholder="t('topbar.searchPlaceholder')"
            :aria-label="t('topbar.searchPlaceholder')"
            autocomplete="off"
          />
        </form>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from '../i18n'

const props = defineProps({
  user: {
    type: Object,
    default: null,
  },
  theme: {
    type: String,
    default: 'light',
  },
  notifications: {
    type: Object,
    default: () => ({ unread_count: 0, items: [] }),
  },
  initialSearch: {
    type: String,
    default: '',
  },
  sidebarOpen: {
    type: Boolean,
    default: false,
  },
  dashboardUrl: {
    type: String,
    required: true,
  },
  accountProfileUrl: {
    type: String,
    required: true,
  },
  accountPasswordUrl: {
    type: String,
    required: true,
  },
  isDesktop: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits([
  'toggle-sidebar',
  'toggle-theme',
  'search',
  'mark-all-notifications',
  'mark-notification',
  'delete-notification',
  'logout',
])

const { t } = useI18n()

const search = ref(props.initialSearch)
const topbarRef = ref(null)
const notificationsOpen = ref(false)
const accountOpen = ref(false)

const notificationsRef = ref(null)
const accountRef = ref(null)

const notificationItems = ref(Array.isArray(props.notifications?.items) ? props.notifications.items : [])

watch(
  () => props.notifications,
  val => {
    notificationItems.value = Array.isArray(val?.items) ? val.items : []
  },
  { deep: true }
)

const unreadCount = computed(() => notificationItems.value.filter(item => !item.read_at).length)
const themeIcon = computed(() => (props.theme === 'dark' ? 'light_mode' : 'dark_mode'))
const themeToggleTitle = computed(() => (props.theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode'))

const displayName = computed(() => {
  if (!props.user) {
    return 'Pengguna'
  }

  const fullFromParts = [props.user.first_name, props.user.last_name]
    .map(value => (typeof value === 'string' ? value.trim() : ''))
    .filter(Boolean)
    .join(' ')

  const candidates = [
    props.user.display_name,
    props.user.full_name,
    fullFromParts,
    props.user.name,
    props.user.first_name,
    props.user.last_name,
    props.user.username,
    props.user.email,
  ]

  const resolved = candidates
    .map(value => (typeof value === 'string' ? value.trim() : ''))
    .find(Boolean)

  return resolved || 'Pengguna'
})

const unreadBadge = computed(() => {
  if (unreadCount.value > 99) return '99+'
  return unreadCount.value > 0 ? String(unreadCount.value) : ''
})

const badgeAria = computed(() => {
  if (unreadCount.value > 99) return '99+'
  return String(unreadCount.value)
})

const closeNotifications = () => {
  notificationsOpen.value = false
}

const closeAccount = () => {
  accountOpen.value = false
}

const submitSearch = () => {
  emit('search', search.value)
}

const markAllNotifications = () => {
  notificationItems.value = notificationItems.value.map(item => ({ ...item, read_at: item.read_at || new Date().toISOString() }))
  fetch(resolveRoute('notifications.read-all'), {
    method: 'POST',
    headers: buildHeaders(),
    credentials: 'same-origin',
  }).catch(() => {})
}

const markNotification = id => {
  notificationItems.value = notificationItems.value.map(item => (item.id === id ? { ...item, read_at: item.read_at || new Date().toISOString() } : item))
  fetch(resolveRoute('notifications.mark', { id }), {
    method: 'POST',
    headers: buildHeaders(),
    credentials: 'same-origin',
  }).catch(() => {})
}

const deleteNotification = id => {
  notificationItems.value = notificationItems.value.filter(item => item.id !== id)
  fetch(resolveRoute('notifications.destroy', { id }), {
    method: 'DELETE',
    headers: buildHeaders(),
    credentials: 'same-origin',
  }).catch(() => {})
}

const logout = () => {
  closeAccount()
  emit('logout')
}

const emitToggleSidebar = () => {
  emit('toggle-sidebar')
}

const emitToggleTheme = () => {
  emit('toggle-theme')
}

const toggleNotifications = () => {
  notificationsOpen.value = !notificationsOpen.value
  if (notificationsOpen.value) {
    accountOpen.value = false
  }
}

const toggleAccount = () => {
  accountOpen.value = !accountOpen.value
  if (accountOpen.value) {
    notificationsOpen.value = false
  }
}

const buildHeaders = () => {
  const token = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || ''
  return {
    'X-CSRF-TOKEN': token,
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
  }
}

const handleClickOutside = event => {
  if (
    notificationsOpen.value &&
    notificationsRef.value &&
    !notificationsRef.value.contains(event.target)
  ) {
    closeNotifications()
  }

  if (accountOpen.value && accountRef.value && !accountRef.value.contains(event.target)) {
    closeAccount()
  }
}

const handleEsc = event => {
  if (event.key === 'Escape') {
    closeNotifications()
    closeAccount()
  }
}

const updateTopbarHeight = () => {
  const el = topbarRef.value
  if (!el || !el.getBoundingClientRect) return
  const height = Math.round(el.getBoundingClientRect().height)
  document.documentElement.style.setProperty('--topbar-h', `${height}px`)
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside, true)
  document.addEventListener('keydown', handleEsc)
  nextTick(updateTopbarHeight)
  window.addEventListener('resize', updateTopbarHeight, { passive: true })
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside, true)
  document.removeEventListener('keydown', handleEsc)
  window.removeEventListener('resize', updateTopbarHeight, { passive: true })
})

watch(
  () => props.initialSearch,
  value => {
    search.value = value
  }
)

watch([notificationsOpen, accountOpen], () => {
  nextTick(updateTopbarHeight)
})

defineExpose({
  setSearch(value) {
    search.value = value
  },
})
</script>

<style scoped>
.topbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 40;
  background: linear-gradient(120deg, #2563eb 0%, #4338ca 45%, #1d4ed8 100%);
  color: #ffffff;
  box-shadow: 0 20px 45px -28px rgba(30, 64, 175, 0.58);
  border-bottom: 1px solid rgba(255, 255, 255, 0.12);
}

.dark .topbar {
  background: linear-gradient(120deg, rgba(15, 23, 42, 0.96) 0%, rgba(30, 41, 59, 0.92) 60%, rgba(30, 64, 175, 0.85) 100%);
  border-bottom-color: rgba(148, 163, 184, 0.18);
  box-shadow: 0 20px 45px -32px rgba(15, 23, 42, 0.9);
}

.topbar__inner,
.topbar__brand,
.topbar__brand-text,
.topbar__logo,
.topbar__left,
.topbar__right,
.topbar__welcome,
.topbar__welcome .welcome,
.topbar__welcome .name,
.topbar-icon-btn,
.topbar__search,
.topbar-search,
.topbar-search input,
.topbar-search__icon {
  color: inherit;
}

.topbar__inner {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
  max-width: min(1536px, 100%);
  margin-inline: auto;
  min-height: var(--topbar-h, 72px);
  padding: 0.75rem 1rem;
  box-sizing: border-box;
}

@media (min-width: 768px) {
  .topbar__inner {
    display: grid;
    grid-template-columns: auto minmax(0, 1fr) auto;
    align-items: center;
    gap: 1rem 1.5rem;
    padding-inline: 1.5rem;
  }
}

@media (min-width: 1024px) {
  .topbar__inner {
    gap: 1.25rem 2rem;
    padding-inline: 2rem;
  }
}

.topbar__left,
.topbar__right {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  min-width: 0;
}

.topbar__right {
  margin-left: auto;
  flex: 1 1 auto;
  gap: 0.65rem;
  flex-wrap: wrap;
  justify-content: flex-end;
  white-space: normal;
}

@media (min-width: 768px) {
  .topbar__right {
    flex-wrap: nowrap;
    gap: 0.9rem;
  }
}

.topbar__brand {
  display: flex;
  align-items: center;
  gap: 1.75rem;
  column-gap: 1.75rem;
  color: inherit;
  font-weight: 900;
  font-size: 1.28rem;
  letter-spacing: -0.02em;
  transition: transform 0.18s ease;
  min-width: 0;
}

.topbar__logo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  color: inherit;
  margin-right: 1.25rem;
  transition: transform 0.18s ease;
}


.topbar__logo-img {
  width: 34px;
  height: 34px;
  display: inline-block;
  object-fit: contain;
  filter: brightness(0) invert(1);
  transition: transform 0.18s ease, filter 0.18s ease;
}

.topbar__brand:hover,
.topbar__brand:focus-visible {
  transform: translateY(-1px);
}

.topbar__brand:active {
  transform: translateY(0);
}


.topbar__brand:hover .topbar__brand-text,
.topbar__brand:focus-visible .topbar__brand-text {
  transform: translateY(-1px);
}

.topbar__brand:hover .topbar__logo-img,
.topbar__brand:focus-visible .topbar__logo-img {
  filter: brightness(0) invert(1);
}

.topbar__brand-text {
  margin-left: 0.35rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  transition: transform 0.18s ease;
  font-family: 'Poppins', 'Inter', 'Segoe UI', 'Helvetica Neue', sans-serif;
  font-weight: 800;
  letter-spacing: -0.03em;
}

.topbar__search {
  flex: 1 1 100%;
  min-width: 0;
  width: 100%;
  display: flex;
  justify-content: center;
}

.topbar-search {
  position: relative;
  width: 100%;
  min-width: 0;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border-radius: 999px;
  padding: 0.65rem 0.95rem;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.18);
  transition: background-color 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
  box-sizing: border-box;
}

@media (min-width: 768px) {
  .topbar-search {
    padding: 0.7rem 1.25rem;
  }
}

.topbar-search:focus-within {
  background: rgba(255, 255, 255, 0.18);
  border-color: rgba(255, 255, 255, 0.35);
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.22);
}

.topbar-search__icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.15rem;
  color: rgba(255, 255, 255, 0.86);
  flex: 0 0 auto;
}

.topbar-search__input {
  flex: 1 1 auto;
  min-width: 0;
  background: transparent;
  border: none;
  color: #ffffff;
  font-size: 0.95rem;
  line-height: 1.35;
  padding: 0;
}

.topbar-search__input::placeholder {
  color: rgba(255, 255, 255, 0.7);
}

.topbar-search__input:focus {
  outline: none;
}

.topbar-icon-btn {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 8px;
  background: transparent;
  color: inherit;
  border: 1px solid transparent;
  transition: all 0.2s ease;
}

.topbar-icon-btn .material-icons {
  color: inherit;
}

.topbar-user-btn .material-icons {
  color: #ffffff !important;
}

.dark .topbar-user-btn .material-icons {
  color: #ffffff !important;
}

.topbar-icon-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  transform: translateY(-1px);
}

.topbar-icon-btn.is-active {
  background: rgba(255, 255, 255, 0.15);
  border-color: rgba(255, 255, 255, 0.55);
}

.topbar__welcome {
  display: none;
  min-width: 0;
  text-align: right;
}

@media (min-width: 768px) {
  .topbar__welcome {
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.15rem;
    max-width: 12rem;
  }
}

.topbar__welcome {
  align-self: center;
}

.topbar__welcome .name {
  max-width: 100%;
}

@media (min-width: 1280px) {
  .topbar__welcome {
    max-width: 14rem;
  }
}

.topbar__welcome .welcome {
  font-size: 0.65rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  opacity: 0.75;
}

.topbar__welcome .name {
  font-weight: 600;
  font-size: 0.9rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.topbar__actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
  justify-content: flex-end;
}

@media (min-width: 768px) {
  .topbar__actions {
    gap: 0.65rem;
    flex-wrap: nowrap;
    white-space: nowrap;
  }
}

.topbar__badge {
  position: absolute;
  top: -0.35rem;
  right: -0.1rem;
  min-width: 1.1rem;
  height: 1.1rem;
  border-radius: 999px;
  background: linear-gradient(135deg, #ef4444, #dc2626);
  color: #fff;
  font-size: 0.58rem;
  font-weight: 700;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.75);
}

.topbar-dropdown {
  position: absolute;
  top: calc(100% + 0.5rem);
  right: 0;
  width: 20rem;
  background: #fff;
  color: #0f172a;
  border-radius: 1rem;
  overflow: hidden;
  box-shadow: 0 28px 60px -28px rgba(15, 23, 42, 0.45);
  border: 1px solid rgba(148, 163, 184, 0.2);
  z-index: 100000;
  inset-inline-end: 0;
  width: min(20rem, calc(100vw - 1.5rem));
  max-width: calc(100vw - 1.5rem);
}

.dark .topbar-dropdown {
  background: #0f172a;
  color: #e2e8f0;
  border-color: rgba(71, 85, 105, 0.4);
  box-shadow: 0 28px 60px -28px rgba(0, 0, 0, 0.85);
}

.topbar-dropdown.topbar-dropdown--account {
  width: min(14rem, calc(100vw - 1.5rem));
}

@media (min-width: 640px) {
  .topbar-dropdown {
    width: 20rem;
  }

  .topbar-dropdown.topbar-dropdown--account {
    width: 14rem;
  }
}

.dropdown-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.75rem 1rem;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  background: rgba(226, 232, 240, 0.35);
}

.dark .dropdown-header {
  background: rgba(30, 41, 59, 0.6);
}

.dropdown-header button {
  font-size: 0.7rem;
  font-weight: 600;
  color: #2563eb;
}

.dropdown-empty {
  padding: 1.5rem;
  text-align: center;
  font-size: 0.85rem;
  color: rgba(51, 65, 85, 0.7);
}

.dropdown-list {
  max-height: 24rem;
  overflow-y: auto;
  padding: 0.75rem 0.9rem;
  box-sizing: border-box;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  max-height: min(24rem, calc(100vh - 8rem));
}

.notif-card {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.75rem;
  align-items: flex-start;
  padding: 0.9rem 1rem;
  border-radius: 16px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
  box-shadow: 0 12px 32px -20px rgba(15, 23, 42, 0.18);
  width: 100%;
  box-sizing: border-box;
  word-break: break-word;
}

.notif-card--unread {
  border-color: #bfdbfe;
  background: #eff6ff;
}

.dark .notif-card {
  border-color: #1f2937;
  background: #0f172a;
}

.dark .notif-card--unread {
  border-color: #2563eb;
  background: #111827;
}

.notif-icon {
  width: 42px;
  height: 42px;
  border-radius: 12px;
  background: transparent;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: #0f172a;
  box-shadow: none;
}

.notif-body {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.notif-head {
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.notif-title {
  font-weight: 700;
  color: #0f172a;
  line-height: 1.2;
  white-space: normal;
}

.notif-dot {
  width: 8px;
  height: 8px;
  border-radius: 999px;
  background: #2563eb;
}

.notif-message {
  font-size: 0.9rem;
  color: #475569;
  white-space: normal;
  line-height: 1.35;
}

.notif-meta {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.8rem;
  color: #64748b;
  flex-wrap: wrap;
}

.notif-link {
  color: #2563eb;
  font-weight: 600;
}

.notif-actions {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.notif-btn {
  width: 34px;
  height: 34px;
  border-radius: 10px;
  background: #e2e8f0;
  color: #0f172a;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #cbd5e1;
  transition: all 0.15s ease;
}

.notif-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 10px 18px -14px rgba(15, 23, 42, 0.4);
}

.notif-btn--danger {
  background: #fee2e2;
  border-color: #fecaca;
  color: #b91c1c;
}

.dark .notif-title {
  color: #e2e8f0;
}
.dark .notif-message {
  color: #cbd5e1;
}
.dark .notif-meta {
  color: #94a3b8;
}
.dark .notif-link {
  color: #93c5fd;
}
.dark .notif-btn {
  background: #1f2937;
  border-color: #111827;
  color: #e2e8f0;
}
.dark .notif-btn--danger {
  background: #7f1d1d;
  border-color: #991b1b;
  color: #fecdd3;
}

.dropdown-item {
  display: flex;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

.dark .dropdown-item {
  border-color: rgba(71, 85, 105, 0.5);
}

.dropdown-item:last-child {
  border-bottom: none;
}

.dropdown-icon {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
}

.dropdown-content {
  flex: 1 1 auto;
  min-width: 0;
}

.dropdown-title {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.9rem;
  font-weight: 600;
}

.dropdown-dot {
  width: 0.5rem;
  height: 0.5rem;
  border-radius: 999px;
  background: #2563eb;
}

.dropdown-message {
  font-size: 0.8rem;
  color: rgba(71, 85, 105, 0.9);
  margin: 0.2rem 0;
}

.dark .dropdown-message {
  color: rgba(226, 232, 240, 0.75);
}

.dropdown-time {
  font-size: 0.7rem;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: rgba(100, 116, 139, 0.75);
}

.dropdown-link {
  display: inline-flex;
  margin-top: 0.3rem;
  font-size: 0.78rem;
  font-weight: 600;
  color: #2563eb;
}

.dropdown-actions {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.dropdown-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border-radius: 0.75rem;
  border: none;
  cursor: pointer;
}

.dropdown-btn--confirm {
  background: rgba(37, 99, 235, 0.14);
  color: #2563eb;
}

.dropdown-btn--delete {
  background: rgba(239, 68, 68, 0.14);
  color: #ef4444;
}

.dropdown-backdrop {
  position: fixed;
  inset: 0;
  background: transparent;
  backdrop-filter: none;
  z-index: 9000;
}

.dropdown-account__header {
  display: flex;
  align-items: center;
  padding: 0.9rem 1rem;
  background: rgba(226, 232, 240, 0.4);
  border-bottom: 1px solid rgba(148, 163, 184, 0.35);
}

.dark .dropdown-account__header {
  background: rgba(30, 41, 59, 0.65);
  border-bottom-color: rgba(71, 85, 105, 0.5);
}

.dropdown-account__heading {
  font-size: 0.72rem;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: rgba(71, 85, 105, 0.9);
}

.dark .dropdown-account__heading {
  color: rgba(226, 232, 240, 0.75);
}

.dropdown-account__body {
  display: flex;
  flex-direction: column;
  padding: 0.35rem 0;
}

.dropdown-account__link {
  display: block;
  padding: 0.65rem 1rem;
  font-size: 0.85rem;
  font-weight: 500;
  color: inherit;
  transition: background-color 0.18s ease, color 0.18s ease;
}

.dropdown-account__link:hover {
  background: rgba(59, 130, 246, 0.12);
  color: #2563eb;
}

.dark .dropdown-account__link:hover {
  background: rgba(59, 130, 246, 0.22);
  color: #bfdbfe;
}

.dropdown-account__footer {
  border-top: 1px solid rgba(226, 232, 240, 0.55);
  padding: 0.75rem 0.75rem 0.85rem;
}

.dark .dropdown-account__footer {
  border-top-color: rgba(71, 85, 105, 0.5);
}

.dropdown-account__logout {
  width: 100%;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  border-radius: 0.85rem;
  padding: 0.6rem 0.9rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #dc2626;
  background: rgba(239, 68, 68, 0.12);
  border: none;
  transition: background-color 0.2s ease, transform 0.2s ease;
  cursor: pointer;
}

.dropdown-account__logout:hover {
  background: rgba(239, 68, 68, 0.18);
  transform: translateY(-1px);
}

.dark .dropdown-account__logout {
  color: #fca5a5;
  background: rgba(248, 113, 113, 0.14);
}

.dark .dropdown-account__logout:hover {
  background: rgba(248, 113, 113, 0.22);
}

.topbar__mobile-menu-btn {
  display: inline-flex;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 10px;
  align-items: center;
  justify-content: center;
  background: rgba(255, 255, 255, 0.1);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
