
<template>
  <div class="flex flex-col gap-6">
    <header class="flex flex-col gap-2">
      <h1 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ t('settings.title') }}</h1>
      <p class="text-sm text-slate-500 dark:text-slate-300">
        {{ t('settings.subtitle') }}
      </p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[260px_1fr]">
      <aside class="rounded-2xl border border-slate-200 bg-white/90 p-4 shadow-sm dark:border-slate-700 dark:bg-slate-900/60">
        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-400">{{ t('settings.sections') }}</p>
        <div class="flex flex-row gap-2 overflow-auto lg:flex-col lg:gap-1">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            type="button"
            :class="navClass(tab.id)"
            @click="activeTab = tab.id"
          >
            <span class="text-sm font-semibold">{{ tab.label }}</span>
            <span v-if="tab.badge" class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
              {{ tab.badge }}
            </span>
          </button>
        </div>
      </aside>

      <section class="space-y-6">
        <div v-show="activeTab === 'general'" class="space-y-6">
          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ t('settings.general.appIdentityTitle') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">{{ t('settings.general.appIdentitySubtitle') }}</p>
              </div>
              <span v-if="generalDirty" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                {{ t('common.unsavedChanges') }}
              </span>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.general.appName') }}</label>
                <input v-model="generalForm.app_name" type="text" class="form-input mt-2" />
                <p v-if="generalForm.errors.app_name" class="form-error">{{ generalForm.errors.app_name }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.general.defaultPageSize') }}</label>
                <input v-model.number="generalForm.default_page_size" type="number" min="5" max="200" class="form-input mt-2" />
                <p v-if="generalForm.errors.default_page_size" class="form-error">{{ generalForm.errors.default_page_size }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.general.timezone') }}</label>
                <SearchableSelect
                  v-model="generalForm.timezone"
                  :options="timezoneGroups"
                  :search-placeholder="t('settings.general.timezoneSearch')"
                  accent="subtle"
                  class="mt-2"
                />
                <p v-if="generalForm.errors.timezone" class="form-error">{{ generalForm.errors.timezone }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.general.dateFormat') }}</label>
                <FancySelect v-model="generalForm.date_format" :options="dateFormatOptions" accent="subtle" class="mt-2" />
                <p v-if="generalForm.errors.date_format" class="form-error">{{ generalForm.errors.date_format }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.general.locale') }}</label>
                <FancySelect v-model="generalForm.locale" :options="localeOptions" accent="subtle" class="mt-2" />
                <p v-if="generalForm.errors.locale" class="form-error">{{ generalForm.errors.locale }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.general.logo') }}</label>
                <div class="mt-2 flex flex-wrap items-center gap-3">
                  <div class="flex h-16 w-16 items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-50 text-xs text-slate-400 dark:border-slate-600 dark:bg-slate-800">
                    <img v-if="logoPreview" :src="logoPreview" alt="App logo" class="h-14 w-14 rounded-lg object-cover" />
                    <span v-else>{{ t('settings.general.logo') }}</span>
                  </div>
                  <div class="flex flex-col gap-2">
                    <input type="file" accept="image/*" @change="handleLogoChange" />
                    <button
                      v-if="logoPreview"
                      type="button"
                      class="text-left text-xs font-semibold text-rose-500"
                      @click="clearLogo"
                    >
                      {{ t('settings.general.removeLogo') }}
                    </button>
                  </div>
                </div>
                <p v-if="generalForm.errors.app_logo" class="form-error">{{ generalForm.errors.app_logo }}</p>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button type="button" class="btn-primary" :disabled="generalForm.processing" @click="saveGeneral">
                {{ t('common.saveChanges') }}
              </button>
              <button type="button" class="btn-ghost" :disabled="generalForm.processing" @click="resetGeneral">
                {{ t('common.cancel') }}
              </button>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ t('settings.general.maintenanceTitle') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">{{ t('settings.general.maintenanceSubtitle') }}</p>
              </div>
              <span v-if="generalDirty" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                {{ t('common.unsavedChanges') }}
              </span>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.general.maintenanceMode') }}</p>
                    <p class="text-xs text-slate-500">{{ t('settings.general.maintenanceHelp') }}</p>
                  </div>
                  <label class="relative inline-flex cursor-pointer items-center">
                    <input v-model="generalForm.maintenance_enabled" type="checkbox" class="sr-only peer" :disabled="!features.maintenance_controls" />
                    <div class="h-6 w-11 rounded-full bg-slate-300 peer-checked:bg-blue-600"></div>
                    <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                  </label>
                </div>
                <textarea
                  v-model="generalForm.maintenance_message"
                  rows="2"
                  class="form-input mt-3"
                  :placeholder="t('settings.general.maintenanceMessage')"
                  :disabled="!features.maintenance_controls"
                ></textarea>
                <p v-if="generalForm.errors.maintenance_message" class="form-error">{{ generalForm.errors.maintenance_message }}</p>
                <p v-if="!features.maintenance_controls" class="mt-2 text-xs text-amber-600">
                  {{ t('settings.general.maintenanceDisabled') }}
                </p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.general.announcementBanner') }}</p>
                    <p class="text-xs text-slate-500">{{ t('settings.general.announcementHelp') }}</p>
                  </div>
                  <label class="relative inline-flex cursor-pointer items-center">
                    <input v-model="generalForm.announcement_enabled" type="checkbox" class="sr-only peer" />
                    <div class="h-6 w-11 rounded-full bg-slate-300 peer-checked:bg-blue-600"></div>
                    <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                  </label>
                </div>
                <input v-model="generalForm.announcement_title" type="text" class="form-input mt-3" :placeholder="t('settings.general.announcementTitle')" />
                <textarea v-model="generalForm.announcement_body" rows="2" class="form-input mt-2" :placeholder="t('settings.general.announcementMessage')"></textarea>
                <div class="mt-2 grid gap-2 sm:grid-cols-2">
                  <input v-model="generalForm.announcement_starts_at" type="date" class="form-input" :placeholder="t('settings.general.announcementStart')" />
                  <input v-model="generalForm.announcement_ends_at" type="date" class="form-input" :placeholder="t('settings.general.announcementEnd')" />
                </div>
                <p v-if="generalForm.errors.announcement_ends_at" class="form-error">{{ generalForm.errors.announcement_ends_at }}</p>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button type="button" class="btn-primary" :disabled="generalForm.processing" @click="saveGeneral">
                {{ t('common.saveChanges') }}
              </button>
              <button type="button" class="btn-ghost" :disabled="generalForm.processing" @click="resetGeneral">
                {{ t('common.cancel') }}
              </button>
            </div>
          </section>
        </div>
        <div v-show="activeTab === 'security'" class="space-y-6">
          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ t('settings.security.sessionTitle') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">{{ t('settings.security.sessionSubtitle') }}</p>
              </div>
              <span v-if="securityDirty" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                {{ t('common.unsavedChanges') }}
              </span>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-3">
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.sessionTimeout') }}</label>
                <input v-model.number="securityForm.session_timeout_minutes" type="number" min="1" max="1440" class="form-input mt-2" />
                <p v-if="securityForm.errors.session_timeout_minutes" class="form-error">{{ securityForm.errors.session_timeout_minutes }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.maxLoginAttempts') }}</label>
                <input v-model.number="securityForm.max_login_attempts" type="number" min="1" max="20" class="form-input mt-2" />
                <p v-if="securityForm.errors.max_login_attempts" class="form-error">{{ securityForm.errors.max_login_attempts }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.lockout') }}</label>
                <input v-model.number="securityForm.lockout_minutes" type="number" min="1" max="240" class="form-input mt-2" />
                <p v-if="securityForm.errors.lockout_minutes" class="form-error">{{ securityForm.errors.lockout_minutes }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.minPassword') }}</label>
                <input v-model.number="securityForm.password_min_length" type="number" min="6" max="64" class="form-input mt-2" />
              </div>
              <div class="flex flex-col gap-2">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.complexity') }}</label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                  <input v-model="securityForm.password_require_uppercase" type="checkbox" />
                  {{ t('settings.security.requireCase') }}
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                  <input v-model="securityForm.password_require_number" type="checkbox" />
                  {{ t('settings.security.requireNumber') }}
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                  <input v-model="securityForm.password_require_symbol" type="checkbox" />
                  {{ t('settings.security.requireSymbol') }}
                </label>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.require2fa') }}</p>
                    <p class="text-xs text-slate-500">{{ t('settings.security.require2faHelp') }}</p>
                  </div>
                  <label class="relative inline-flex cursor-pointer items-center">
                    <input v-model="securityForm.require_2fa" type="checkbox" class="sr-only peer" :disabled="!features.two_factor" />
                    <div class="h-6 w-11 rounded-full bg-slate-300 peer-checked:bg-blue-600"></div>
                    <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                  </label>
                </div>
                <p v-if="!features.two_factor" class="mt-2 text-xs text-amber-600">
                  {{ t('settings.security.twoFactorDisabled') }}
                </p>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button type="button" class="btn-primary" :disabled="securityForm.processing" @click="saveSecurity">
                {{ t('common.saveChanges') }}
              </button>
              <button type="button" class="btn-ghost" :disabled="securityForm.processing" @click="resetSecurity">
                {{ t('common.cancel') }}
              </button>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ t('settings.security.accessTitle') }}</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">{{ t('settings.security.accessSubtitle') }}</p>
              </div>
              <span v-if="securityDirty" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                {{ t('common.unsavedChanges') }}
              </span>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
              <div class="lg:col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                <div class="flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.enforceIp') }}</p>
                    <p class="text-xs text-slate-500">{{ t('settings.security.enforceIpHelp') }}</p>
                  </div>
                  <label class="relative inline-flex cursor-pointer items-center">
                    <input v-model="securityForm.enforce_ip_restrictions" type="checkbox" class="sr-only peer" :disabled="!features.ip_restrictions" />
                    <div class="h-6 w-11 rounded-full bg-slate-300 peer-checked:bg-blue-600"></div>
                    <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                  </label>
                </div>
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.allowBypass') }}</p>
                    <p class="text-xs text-slate-500">{{ t('settings.security.allowBypassHelp') }}</p>
                  </div>
                  <label class="relative inline-flex cursor-pointer items-center">
                    <input v-model="securityForm.allow_superadmin_ip_bypass" type="checkbox" class="sr-only peer" :disabled="!features.ip_restrictions" />
                    <div class="h-6 w-11 rounded-full bg-slate-300 peer-checked:bg-blue-600"></div>
                    <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                  </label>
                </div>
                <p v-if="!features.ip_restrictions" class="mt-3 text-xs text-amber-600">
                  {{ t('settings.security.ipDisabled') }}
                </p>
                <p class="mt-3 text-xs text-slate-500">{{ accessPolicySummary }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.ipAllowlist') }}</label>
                <textarea v-model="ipAllowlistText" rows="4" class="form-input mt-2" :placeholder="t('settings.security.ipPlaceholder')"></textarea>
                <p v-if="securityForm.errors['ip_allowlist']" class="form-error">{{ securityForm.errors['ip_allowlist'] }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.ipBlocklist') }}</label>
                <textarea v-model="ipBlocklistText" rows="4" class="form-input mt-2" :placeholder="t('settings.security.ipPlaceholder')"></textarea>
                <p v-if="securityForm.errors['ip_blocklist']" class="form-error">{{ securityForm.errors['ip_blocklist'] }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ t('settings.security.emailDomains') }}</label>
                <textarea v-model="emailDomainsText" rows="3" class="form-input mt-2" :placeholder="t('settings.security.domainPlaceholder')"></textarea>
                <p v-if="securityForm.errors['allowed_email_domains']" class="form-error">{{ securityForm.errors['allowed_email_domains'] }}</p>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button type="button" class="btn-primary" :disabled="securityForm.processing" @click="saveSecurity">
                Save changes
              </button>
              <button type="button" class="btn-ghost" :disabled="securityForm.processing" @click="resetSecurity">
                Cancel
              </button>
            </div>
          </section>
        </div>

        <div v-show="activeTab === 'notifications'" class="space-y-6">
          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">SMTP Configuration</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Configure outbound email delivery.</p>
              </div>
              <span v-if="notificationsDirty" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                Unsaved changes
              </span>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-2">
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">SMTP host</label>
                <input v-model="notificationsForm.smtp_host" type="text" class="form-input mt-2" />
                <p v-if="notificationsForm.errors.smtp_host" class="form-error">{{ notificationsForm.errors.smtp_host }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">SMTP port</label>
                <input v-model.number="notificationsForm.smtp_port" type="number" min="1" max="65535" class="form-input mt-2" />
                <p v-if="notificationsForm.errors.smtp_port" class="form-error">{{ notificationsForm.errors.smtp_port }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">SMTP username</label>
                <input v-model="notificationsForm.smtp_username" type="text" class="form-input mt-2" />
                <p v-if="notificationsForm.errors.smtp_username" class="form-error">{{ notificationsForm.errors.smtp_username }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">SMTP password</label>
                <input v-model="notificationsForm.smtp_password" type="password" class="form-input mt-2" placeholder="Leave blank to keep existing" />
                <p v-if="smtpPasswordSet" class="mt-1 text-xs text-slate-500">Saved password: ********</p>
                <button
                  v-if="smtpPasswordSet"
                  type="button"
                  class="mt-1 text-xs font-semibold text-rose-500"
                  @click="clearSmtpPassword"
                >
                  Clear saved password
                </button>
                <p v-if="notificationsForm.errors.smtp_password" class="form-error">{{ notificationsForm.errors.smtp_password }}</p>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Encryption</label>
                <FancySelect
                  v-model="notificationsForm.smtp_encryption"
                  :options="encryptionOptions"
                  accent="subtle"
                  class="mt-2"
                />
                <p v-if="notificationsForm.errors.smtp_encryption" class="form-error">{{ notificationsForm.errors.smtp_encryption }}</p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Test email</p>
                <p class="text-xs text-slate-500">Send a test message to confirm SMTP.</p>
                <input v-model="testEmailRecipient" type="email" class="form-input mt-3" placeholder="Recipient email" />
                <button type="button" class="btn-secondary mt-3" :disabled="smtpTesting" @click="sendTestEmail">
                  {{ smtpTesting ? 'Sending...' : 'Send test email' }}
                </button>
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button type="button" class="btn-primary" :disabled="notificationsForm.processing" @click="saveNotifications">
                Save changes
              </button>
              <button type="button" class="btn-ghost" :disabled="notificationsForm.processing" @click="resetNotifications">
                Cancel
              </button>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Default Notification Rules</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Configure what events send alerts.</p>
              </div>
              <span v-if="notificationsDirty" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                Unsaved changes
              </span>
            </div>
            <div class="mt-6 grid gap-3">
              <label class="flex items-center gap-2 text-sm text-slate-600">
                <input v-model="notificationsForm.notify_ticket_created" type="checkbox" />
                Ticket created
              </label>
              <label class="flex items-center gap-2 text-sm text-slate-600">
                <input v-model="notificationsForm.notify_ticket_assigned" type="checkbox" />
                Ticket assigned
              </label>
              <label class="flex items-center gap-2 text-sm text-slate-600">
                <input v-model="notificationsForm.notify_ticket_status_changed" type="checkbox" />
                Status changed
              </label>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button type="button" class="btn-primary" :disabled="notificationsForm.processing" @click="saveNotifications">
                Save changes
              </button>
              <button type="button" class="btn-ghost" :disabled="notificationsForm.processing" @click="resetNotifications">
                Cancel
              </button>
            </div>
          </section>
        </div>
        <div v-show="activeTab === 'defaults'" class="space-y-6">
          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Work Item Defaults</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Set defaults for tickets, tasks, and projects.</p>
              </div>
              <span v-if="defaultsDirty" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                Unsaved changes
              </span>
            </div>

            <div class="mt-6 grid gap-5 lg:grid-cols-3">
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Ticket default status</label>
                <FancySelect v-model="defaultsForm.ticket_default_status" :options="statusOptions" accent="subtle" class="mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Task default status</label>
                <FancySelect v-model="defaultsForm.task_default_status" :options="statusOptions" accent="subtle" class="mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Project default status</label>
                <FancySelect v-model="defaultsForm.project_default_status" :options="statusOptions" accent="subtle" class="mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Default priority</label>
                <FancySelect v-model="defaultsForm.default_priority" :options="priorityOptions" accent="subtle" class="mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Default SLA (hours)</label>
                <input v-model.number="defaultsForm.default_sla_hours" type="number" min="1" max="720" class="form-input mt-2" />
              </div>
              <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                <div class="flex items-center justify-between gap-3">
                  <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Auto-assign</p>
                    <p class="text-xs text-slate-500">Assign by role when creating work items.</p>
                  </div>
                  <label class="relative inline-flex cursor-pointer items-center">
                    <input v-model="defaultsForm.auto_assign_enabled" type="checkbox" class="sr-only peer" />
                    <div class="h-6 w-11 rounded-full bg-slate-300 peer-checked:bg-blue-600"></div>
                    <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                  </label>
                </div>
                <select v-model="defaultsForm.auto_assign_role" class="form-input mt-3" :disabled="!defaultsForm.auto_assign_enabled">
                  <option value="">Select role</option>
                  <option v-for="role in options.autoAssignRoles" :key="role.id" :value="role.name">
                    {{ role.label }}
                  </option>
                </select>
                <select v-model="defaultsForm.auto_assign_strategy" class="form-input mt-2" :disabled="!defaultsForm.auto_assign_enabled">
                  <option value="round_robin">Round robin</option>
                  <option value="least_load">Least load</option>
                </select>
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Ticket numbering format</label>
                <input v-model="defaultsForm.ticket_numbering_format" type="text" class="form-input mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Task numbering format</label>
                <input v-model="defaultsForm.task_numbering_format" type="text" class="form-input mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Project numbering format</label>
                <input v-model="defaultsForm.project_numbering_format" type="text" class="form-input mt-2" />
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button type="button" class="btn-primary" :disabled="defaultsForm.processing" @click="saveDefaults">
                Save changes
              </button>
              <button type="button" class="btn-ghost" :disabled="defaultsForm.processing" @click="resetDefaults">
                Cancel
              </button>
            </div>
          </section>
        </div>

        <div v-show="activeTab === 'roles'" class="space-y-6">
          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Roles and Permissions</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Manage access matrix. Built-in roles are read-only.</p>
              </div>
              <span v-if="rolesDirty" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">
                Unsaved changes
              </span>
            </div>

            <div class="mt-6 overflow-auto rounded-xl border border-slate-200 dark:border-slate-700">
              <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-100 text-xs uppercase text-slate-500 dark:bg-slate-800">
                  <tr>
                    <th class="px-4 py-3">Role</th>
                    <th v-for="permission in roleMatrix.permissions" :key="permission.id" class="px-4 py-3">
                      {{ permission.label }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="roleRows.length === 0">
                    <td :colspan="roleMatrix.permissions.length + 1" class="px-4 py-6 text-center text-sm text-slate-400">
                      No roles available.
                    </td>
                  </tr>
                  <tr v-for="role in roleRows" :key="role.id" class="border-t border-slate-200 dark:border-slate-700">
                    <td class="px-4 py-3 font-semibold text-slate-700 dark:text-slate-200">
                      {{ role.label }}
                      <span v-if="role.is_builtin" class="ml-2 text-xs text-slate-400">(built-in)</span>
                    </td>
                    <td v-for="permission in roleMatrix.permissions" :key="permission.id" class="px-4 py-3 text-center">
                      <input
                        type="checkbox"
                        :disabled="role.is_builtin"
                        :checked="role.permissions.includes(permission.name)"
                        @change="togglePermission(role, permission.name)"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button type="button" class="btn-primary" :disabled="rolesSaving" @click="saveRoles">
                Save changes
              </button>
              <button type="button" class="btn-ghost" :disabled="rolesSaving" @click="resetRoles">
                Cancel
              </button>
            </div>
          </section>

            <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Impersonation</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-300">Temporarily log in as another user (audited).</p>
                </div>
              </div>
              <div class="mt-4">
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/60">
                  <div>
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">Allow impersonation</p>
                    <p class="text-xs text-slate-500">Requires `FEATURE_IMPERSONATION=true`.</p>
                  </div>
                  <label class="relative inline-flex cursor-pointer items-center">
                    <input v-model="securityForm.allow_impersonation" type="checkbox" class="sr-only peer" :disabled="!features.impersonation" />
                    <div class="h-6 w-11 rounded-full bg-slate-300 peer-checked:bg-blue-600"></div>
                    <span class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"></span>
                  </label>
                </div>
                <p v-if="!features.impersonation" class="mt-3 text-sm text-amber-600">
                  Impersonation is disabled in `config/features.php`.
                </p>
                <p v-else-if="!securityForm.allow_impersonation" class="mt-3 text-sm text-amber-600">
                  Impersonation is turned off in Security settings.
                </p>
                <div v-else class="mt-4 flex flex-wrap items-center gap-3">
                  <input v-model="impersonateUserId" type="number" min="1" class="form-input w-40" placeholder="User ID" />
                  <button type="button" class="btn-secondary" @click="startImpersonate">
                    Impersonate user
                  </button>
                  <button type="button" class="btn-ghost" @click="stopImpersonate">
                    Stop impersonation
                  </button>
                </div>
                <div class="mt-4 flex flex-wrap items-center gap-3">
                  <button type="button" class="btn-primary" :disabled="securityForm.processing" @click="saveSecurity">
                    Save security settings
                  </button>
                  <button type="button" class="btn-ghost" :disabled="securityForm.processing" @click="resetSecurity">
                    Cancel
                  </button>
                </div>
              </div>
            </section>
        </div>

        <div v-show="activeTab === 'data'" class="space-y-6">
          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Export Data</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Generate CSV or PDF exports with filters.</p>
              </div>
            </div>

            <div class="mt-6 grid gap-4 lg:grid-cols-3">
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Dataset</label>
                <FancySelect v-model="exportForm.type" :options="exportDatasetOptions" accent="subtle" class="mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Format</label>
                <FancySelect v-model="exportForm.format" :options="exportFormatOptions" accent="subtle" class="mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Limit rows</label>
                <input v-model.number="exportForm.limit" type="number" min="1" max="5000" class="form-input mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Search</label>
                <input v-model="exportForm.q" type="text" class="form-input mt-2" placeholder="Keyword" />
              </div>
              <div v-if="exportForm.type !== 'users'">
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Status</label>
                <FancySelect v-model="exportForm.status" :options="exportStatusOptions" accent="subtle" class="mt-2" />
              </div>
              <div v-else>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">Role</label>
                <FancySelect v-model="exportForm.role" :options="exportRoleOptions" accent="subtle" class="mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">From</label>
                <FancySelect v-model="exportFromValue" :options="exportFromOptions" accent="subtle" class="mt-2" />
              </div>
              <div>
                <label class="text-sm font-semibold text-slate-700 dark:text-slate-200">To</label>
                <FancySelect v-model="exportToValue" :options="exportToOptions" accent="subtle" class="mt-2" />
              </div>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button type="button" class="btn-primary" @click="runExport">
                Export data
              </button>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">System Health</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Live environment diagnostics.</p>
              </div>
              <button type="button" class="btn-ghost" :disabled="healthLoading" @click="loadHealth">
                Refresh
              </button>
            </div>

            <div v-if="healthLoading" class="mt-6 grid gap-3 sm:grid-cols-2">
              <div v-for="i in 4" :key="i" class="h-16 rounded-xl bg-slate-100 animate-pulse dark:bg-slate-800/60"></div>
            </div>
            <div v-else-if="healthError" class="mt-4 text-sm text-rose-500">{{ healthError }}</div>
            <div v-else class="mt-6 grid gap-4 sm:grid-cols-2">
              <div v-for="item in healthItems" :key="item.label" class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                <p class="text-xs uppercase text-slate-400">{{ item.label }}</p>
                <p class="mt-1 text-sm font-semibold text-slate-700 dark:text-slate-200">{{ item.value }}</p>
              </div>
            </div>
          </section>

          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
            <div class="flex flex-wrap items-center justify-between gap-3">
              <div>
                <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">System Actions</h2>
                <p class="text-sm text-slate-500 dark:text-slate-300">Clear caches or rebuild indexes.</p>
              </div>
            </div>
              <div class="mt-6 flex flex-wrap items-center gap-3">
                <button
                  type="button"
                  class="btn-secondary"
                  :disabled="systemActionLoading || !features.cache_actions || systemActionsDisabled"
                  @click="confirmSystemAction('clear')"
                >
                  Clear caches
                </button>
                <button
                  type="button"
                  class="btn-ghost"
                  :disabled="systemActionLoading || !features.rebuild_indexes || systemActionsDisabled"
                  @click="confirmSystemAction('rebuild')"
                >
                  Rebuild indexes
                </button>
                <p v-if="systemActionsDisabled" class="text-xs text-amber-600">
                  System actions are disabled in production. Set `FEATURE_SYSTEM_ACTIONS_PROD=true` to allow them.
                </p>
                <p v-else-if="!features.cache_actions" class="text-xs text-slate-400">
                  Cache actions are disabled in `config/features.php`.
                </p>
                <p v-else-if="!features.rebuild_indexes" class="text-xs text-slate-400">
                  Index rebuild is disabled in `config/features.php`.
                </p>
              </div>
            </section>
        </div>

        <div v-show="activeTab === 'audit'" class="space-y-6">
          <section class="rounded-2xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-700 dark:bg-slate-900/70">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <h2 class="text-lg font-semibold text-slate-900 dark:text-slate-100">Audit Log</h2>
                  <p class="text-sm text-slate-500 dark:text-slate-300">Track changes across settings and system actions.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                  <input
                    v-model="auditQuery"
                    type="search"
                    class="form-input w-56"
                    placeholder="Search logs"
                    @keyup.enter="fetchAudit(1)"
                  />
                  <button type="button" class="btn-ghost" :disabled="auditLoading" @click="fetchAudit(1)">
                    Refresh
                  </button>
                </div>
              </div>

            <div v-if="auditLoading" class="mt-6 grid gap-3">
              <div v-for="i in 4" :key="i" class="h-16 rounded-xl bg-slate-100 animate-pulse dark:bg-slate-800/60"></div>
            </div>
            <div v-else-if="auditError" class="mt-4 text-sm text-rose-500">{{ auditError }}</div>
            <div v-else-if="auditRows.length === 0" class="mt-6 text-sm text-slate-400">
              No audit entries yet.
            </div>
            <div v-else class="mt-6 overflow-auto rounded-xl border border-slate-200 dark:border-slate-700">
              <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-100 text-xs uppercase text-slate-500 dark:bg-slate-800">
                  <tr>
                    <th class="px-4 py-3">When</th>
                    <th class="px-4 py-3">Actor</th>
                    <th class="px-4 py-3">Action</th>
                    <th class="px-4 py-3">Field</th>
                    <th class="px-4 py-3">Before</th>
                    <th class="px-4 py-3">After</th>
                    <th class="px-4 py-3">IP</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in auditRows" :key="row.id" class="border-t border-slate-200 dark:border-slate-700">
                    <td class="px-4 py-3 text-xs text-slate-500">{{ formatDate(row.created_at) }}</td>
                    <td class="px-4 py-3">{{ row.actor?.name || 'System' }}</td>
                    <td class="px-4 py-3">{{ row.action }}</td>
                    <td class="px-4 py-3">{{ row.group }} {{ row.key ? '/' + row.key : '' }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ row.old_value }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ row.new_value }}</td>
                    <td class="px-4 py-3 text-xs text-slate-500">{{ row.ip_address || '-' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div v-if="auditMeta.last_page > 1" class="mt-4 flex items-center justify-between text-sm text-slate-500">
              <button type="button" class="btn-ghost" :disabled="auditMeta.current_page <= 1" @click="fetchAudit(auditMeta.current_page - 1)">
                Previous
              </button>
              <span>Page {{ auditMeta.current_page }} of {{ auditMeta.last_page }}</span>
              <button type="button" class="btn-ghost" :disabled="auditMeta.current_page >= auditMeta.last_page" @click="fetchAudit(auditMeta.current_page + 1)">
                Next
              </button>
            </div>
          </section>
        </div>
      </section>
    </div>

    <div class="pointer-events-none fixed bottom-6 right-6 z-50 flex flex-col gap-3">
      <transition-group name="toast">
        <div v-for="toast in toasts" :key="toast.id" class="toast" :class="toast.type">
          {{ toast.message }}
        </div>
      </transition-group>
    </div>

    <div v-if="confirmDialog.open" class="fixed inset-0 z-40 flex items-center justify-center bg-slate-900/60 p-4">
      <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-lg dark:border-slate-700 dark:bg-slate-900">
        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ confirmDialog.title }}</h3>
        <p class="mt-2 text-sm text-slate-500">{{ confirmDialog.message }}</p>
        <div class="mt-6 flex items-center justify-end gap-3">
          <button type="button" class="btn-ghost" @click="closeConfirm">Cancel</button>
          <button type="button" class="btn-primary" @click="runConfirmDialog">Confirm</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import FancySelect from '@/Components/FancySelect.vue';
import SearchableSelect from '@/Components/SearchableSelect.vue';
import { useI18n } from '@/i18n';
import resolveRoute from '@/utils/resolveRoute';

const props = defineProps({
  settings: { type: Object, default: () => ({}) },
  settingsMeta: { type: Object, default: () => ({}) },
  options: { type: Object, default: () => ({}) },
  roleMatrix: { type: Object, default: () => ({ roles: [], permissions: [] }) },
  features: { type: Object, default: () => ({}) },
});

const page = usePage();
const { t, setLocale } = useI18n();
const options = computed(() => ({
  timezones: [],
  dateFormats: [],
  locales: [],
  statusOptions: [],
  priorityOptions: [],
  autoAssignRoles: [],
  ...(props.options || {}),
}));
const timezoneList = computed(() => options.value.timezones ?? []);
const dateFormatOptions = computed(() =>
  (options.value.dateFormats ?? []).map(format => ({
    label: format?.label ?? format?.value ?? format,
    value: format?.value ?? format?.label ?? format,
  }))
);
const timezoneGroups = computed(() => {
  const groups = new Map();
  (timezoneList.value ?? []).forEach(zone => {
    const [region, city] = String(zone).split('/');
    if (!region) return;
    if (!groups.has(region)) {
      groups.set(region, []);
    }
    groups.get(region).push({
      label: city ? city.replace('_', ' ') : region,
      value: zone,
    });
  });
  return Array.from(groups.entries())
    .sort(([a], [b]) => a.localeCompare(b))
    .map(([region, items]) => ({
      label: region,
      value: region,
      children: items.sort((a, b) => a.label.localeCompare(b.label)),
    }));
});
const localeOptions = computed(() =>
  (options.value.locales ?? []).map(locale => ({
    label: locale?.label ?? locale?.value ?? locale,
    value: locale?.value ?? locale?.label ?? locale,
  }))
);
const statusOptions = computed(() =>
  (options.value.statusOptions ?? []).map(option => ({
    label: option?.label ?? option?.value ?? option,
    value: option?.value ?? option?.label ?? option,
  }))
);
const exportStatusOptions = computed(() => [{ label: 'All', value: '' }, ...statusOptions.value]);
const priorityOptions = computed(() =>
  (options.value.priorityOptions ?? []).map(option => ({
    label: option?.label ?? option?.value ?? option,
    value: option?.value ?? option?.label ?? option,
  }))
);
const resolvedTimezone = (() => {
  try {
    return Intl.DateTimeFormat().resolvedOptions().timeZone;
  } catch (error) {
    return null;
  }
})();
const commonTimezones = ['UTC', 'Asia/Jakarta', 'Asia/Singapore', 'Europe/London', 'America/New_York', 'America/Los_Angeles'];
const timezoneSuggestions = computed(() => {
  const list = [];
  const pushUnique = value => {
    if (!value) return;
    if (!timezoneList.value.includes(value)) return;
    if (!list.includes(value)) list.push(value);
  };
  pushUnique(resolvedTimezone);
  commonTimezones.forEach(pushUnique);
  return list;
});
const encryptionOptions = [
  { label: 'None', value: 'none' },
  { label: 'TLS', value: 'tls' },
  { label: 'SSL', value: 'ssl' },
];
const exportDatasetOptions = [
  { label: 'Tickets', value: 'tickets' },
  { label: 'Tasks', value: 'tasks' },
  { label: 'Projects', value: 'projects' },
  { label: 'Users', value: 'users' },
];
const exportFormatOptions = [
  { label: 'CSV', value: 'csv' },
  { label: 'PDF', value: 'pdf' },
];
const roleMatrix = computed(() => ({
  roles: [],
  permissions: [],
  ...(props.roleMatrix || {}),
}));
const features = computed(() => ({
  two_factor: false,
  impersonation: false,
  ip_restrictions: false,
  maintenance_controls: true,
  cache_actions: true,
  rebuild_indexes: false,
  system_actions_in_production: false,
  environment: 'local',
  ...(props.features || {}),
}));
const roles = computed(() => (page.props.auth?.user?.roles ?? []).map(role => String(role).toLowerCase()));
const isSuperAdmin = computed(() => roles.value.includes('superadmin'));

if (!isSuperAdmin.value) {
  router.visit('/403', { replace: true });
}

const tabs = computed(() => [
  { id: 'general', label: t('settings.tabs.general'), badge: generalDirty.value ? '!' : '' },
  { id: 'security', label: t('settings.tabs.security'), badge: securityDirty.value ? '!' : '' },
  { id: 'notifications', label: t('settings.tabs.notifications'), badge: notificationsDirty.value ? '!' : '' },
  { id: 'defaults', label: t('settings.tabs.defaults'), badge: defaultsDirty.value ? '!' : '' },
  { id: 'roles', label: t('settings.tabs.roles'), badge: rolesDirty.value ? '!' : '' },
  { id: 'data', label: t('settings.tabs.data'), badge: '' },
  { id: 'audit', label: t('settings.tabs.audit'), badge: '' },
]);

const activeTab = ref('general');
const navClass = id => [
  'flex items-center justify-between rounded-xl px-3 py-2 text-left transition',
  activeTab.value === id
    ? 'bg-blue-600 text-white'
    : 'text-slate-600 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-800',
];

const formSnapshot = (form, omit = []) => {
  const data = { ...form.data() };
  omit.forEach(key => delete data[key]);
  return JSON.parse(JSON.stringify(data));
};

const generalForm = useForm({
  app_name: props.settings.general?.app_name ?? '',
  timezone: props.settings.general?.timezone ?? 'UTC',
  date_format: props.settings.general?.date_format ?? 'd/m/Y',
  locale: props.settings.general?.locale ?? 'en',
  default_page_size: props.settings.general?.default_page_size ?? 10,
  maintenance_enabled: props.settings.general?.maintenance_enabled ?? false,
  maintenance_message: props.settings.general?.maintenance_message ?? '',
  announcement_enabled: props.settings.general?.announcement_enabled ?? false,
  announcement_title: props.settings.general?.announcement_title ?? '',
  announcement_body: props.settings.general?.announcement_body ?? '',
  announcement_starts_at: props.settings.general?.announcement_starts_at ?? '',
  announcement_ends_at: props.settings.general?.announcement_ends_at ?? '',
  app_logo: null,
  app_logo_clear: false,
});

setLocale(generalForm.locale || 'en');

const generalInitial = ref(formSnapshot(generalForm, ['app_logo', 'app_logo_clear']));
const generalDirty = computed(() => JSON.stringify(formSnapshot(generalForm, ['app_logo', 'app_logo_clear'])) !== JSON.stringify(generalInitial.value));
const logoPreview = ref(props.settingsMeta?.logo_url ?? null);

const handleLogoChange = event => {
  const file = event.target.files?.[0];
  if (!file) return;
  generalForm.app_logo = file;
  generalForm.app_logo_clear = false;
  logoPreview.value = URL.createObjectURL(file);
};

const clearLogo = () => {
  generalForm.app_logo = null;
  generalForm.app_logo_clear = true;
  logoPreview.value = null;
};

const saveGeneral = () => {
  const shouldConfirm = generalForm.maintenance_enabled && !generalInitial.value.maintenance_enabled;
  const submit = () => {
    generalForm.post(resolveRoute('settings.general.update'), {
      forceFormData: true,
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        generalInitial.value = formSnapshot(generalForm, ['app_logo', 'app_logo_clear']);
        generalForm.app_logo = null;
        generalForm.app_logo_clear = false;
        pushToast('success', t('toasts.generalSaved'));
        setLocale(generalForm.locale);
      },
      onError: () => {
        pushToast('error', t('toasts.generalFailed'));
      },
    });
  };

  if (shouldConfirm) {
    openConfirm('Enable maintenance mode?', 'This will put the system into maintenance mode.', submit);
  } else {
    submit();
  }
};

const resetGeneral = () => {
  generalForm.reset();
  generalForm.clearErrors();
  generalForm.app_logo = null;
  generalForm.app_logo_clear = false;
  generalInitial.value = formSnapshot(generalForm, ['app_logo', 'app_logo_clear']);
  logoPreview.value = props.settingsMeta?.logo_url ?? null;
};

const securityForm = useForm({
  enforce_ip_restrictions: props.settings.security?.enforce_ip_restrictions ?? false,
  allow_superadmin_ip_bypass: props.settings.security?.allow_superadmin_ip_bypass ?? true,
  session_timeout_minutes: props.settings.security?.session_timeout_minutes ?? 30,
  max_login_attempts: props.settings.security?.max_login_attempts ?? 5,
  lockout_minutes: props.settings.security?.lockout_minutes ?? 15,
  password_min_length: props.settings.security?.password_min_length ?? 8,
  password_require_uppercase: props.settings.security?.password_require_uppercase ?? false,
  password_require_number: props.settings.security?.password_require_number ?? true,
  password_require_symbol: props.settings.security?.password_require_symbol ?? false,
  require_2fa: props.settings.security?.require_2fa ?? false,
  allow_impersonation: props.settings.security?.allow_impersonation ?? false,
  ip_allowlist: props.settings.security?.ip_allowlist ?? [],
  ip_blocklist: props.settings.security?.ip_blocklist ?? [],
  allowed_email_domains: props.settings.security?.allowed_email_domains ?? [],
});

const ipAllowlistText = ref(securityForm.ip_allowlist.join('\n'));
const ipBlocklistText = ref(securityForm.ip_blocklist.join('\n'));
const emailDomainsText = ref(securityForm.allowed_email_domains.join('\n'));

const securityInitial = ref(formSnapshot(securityForm));
const securityDirty = computed(() => JSON.stringify(formSnapshot(securityForm)) !== JSON.stringify(securityInitial.value));

const parseList = text => text.split(/\r?\n/).map(item => item.trim()).filter(Boolean);

const accessPolicySummary = computed(() => {
  const allowCount = parseList(ipAllowlistText.value).length;
  const blockCount = parseList(ipBlocklistText.value).length;
  const enforced = securityForm.enforce_ip_restrictions;
  const bypass = securityForm.allow_superadmin_ip_bypass;

  if (!features.value.ip_restrictions) {
    return t('settings.security.ipStoredOnly');
  }

  if (!enforced) {
    return `${t('settings.security.ipEnforcementOff')} ${t('settings.security.ipAllowlist')}: ${allowCount || t('common.none')}, ${t('settings.security.ipBlocklist')}: ${blockCount || t('common.none')}.`;
  }

  return `${t('settings.security.ipEnforcementOn')} ${t('settings.security.ipAllowlist')}: ${allowCount || t('common.none')}, ${t('settings.security.ipBlocklist')}: ${blockCount || t('common.none')}, ${t('settings.security.superadminBypass')}: ${bypass ? t('common.on') : t('common.off')}.`;
});

watch(ipAllowlistText, value => {
  securityForm.ip_allowlist = parseList(value);
});

watch(ipBlocklistText, value => {
  securityForm.ip_blocklist = parseList(value);
});

watch(emailDomainsText, value => {
  securityForm.allowed_email_domains = parseList(value);
});

const saveSecurity = () => {
  securityForm.post(resolveRoute('settings.security.update'), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      securityInitial.value = formSnapshot(securityForm);
      pushToast('success', t('toasts.securitySaved'));
    },
    onError: () => {
      pushToast('error', t('toasts.securityFailed'));
    },
  });
};

const resetSecurity = () => {
  securityForm.reset();
  securityForm.clearErrors();
  ipAllowlistText.value = securityForm.ip_allowlist.join('\n');
  ipBlocklistText.value = securityForm.ip_blocklist.join('\n');
  emailDomainsText.value = securityForm.allowed_email_domains.join('\n');
  securityInitial.value = formSnapshot(securityForm);
};

const notificationsForm = useForm({
  smtp_host: props.settings.notifications?.smtp_host ?? '',
  smtp_port: props.settings.notifications?.smtp_port ?? 587,
  smtp_username: props.settings.notifications?.smtp_username ?? '',
  smtp_password: '',
  smtp_password_clear: false,
  smtp_encryption: props.settings.notifications?.smtp_encryption ?? 'tls',
  notify_ticket_created: props.settings.notifications?.notify_ticket_created ?? true,
  notify_ticket_assigned: props.settings.notifications?.notify_ticket_assigned ?? true,
  notify_ticket_status_changed: props.settings.notifications?.notify_ticket_status_changed ?? true,
});

const notificationsInitial = ref(formSnapshot(notificationsForm));
const notificationsDirty = computed(
  () => JSON.stringify(formSnapshot(notificationsForm)) !== JSON.stringify(notificationsInitial.value)
);

const smtpPasswordSet = ref(Boolean(props.settingsMeta?.notifications?.secrets?.smtp_password));
const testEmailRecipient = ref('');
const smtpTesting = ref(false);

const clearSmtpPassword = () => {
  notificationsForm.smtp_password_clear = true;
  notificationsForm.smtp_password = '';
};

const saveNotifications = () => {
  notificationsForm.post(resolveRoute('settings.notifications.update'), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      const hasPassword = notificationsForm.smtp_password_clear
        ? false
        : (notificationsForm.smtp_password !== '' || smtpPasswordSet.value);
      smtpPasswordSet.value = hasPassword;
      notificationsForm.smtp_password = '';
      notificationsForm.smtp_password_clear = false;
      notificationsInitial.value = formSnapshot(notificationsForm);
      pushToast('success', 'Notification settings saved.');
    },
    onError: () => {
      pushToast('error', 'Failed to save notification settings.');
    },
  });
};

const resetNotifications = () => {
  notificationsForm.reset();
  notificationsForm.clearErrors();
  notificationsForm.smtp_password = '';
  notificationsForm.smtp_password_clear = false;
  smtpPasswordSet.value = Boolean(props.settingsMeta?.notifications?.secrets?.smtp_password);
  notificationsInitial.value = formSnapshot(notificationsForm);
};

const sendTestEmail = async () => {
  smtpTesting.value = true;
  try {
    const response = await fetch(resolveRoute('settings.notifications.test'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ recipient: testEmailRecipient.value || null }),
    });
    const payload = await response.json();
    if (!response.ok) {
      throw new Error(payload.message || 'SMTP test failed.');
    }
    pushToast('success', payload.message || 'Test email sent.');
  } catch (error) {
    pushToast('error', error.message || 'SMTP test failed.');
  } finally {
    smtpTesting.value = false;
  }
};

const defaultsForm = useForm({
  ticket_default_status: props.settings.defaults?.ticket_default_status ?? 'new',
  task_default_status: props.settings.defaults?.task_default_status ?? 'new',
  project_default_status: props.settings.defaults?.project_default_status ?? 'new',
  default_priority: props.settings.defaults?.default_priority ?? 'medium',
  default_sla_hours: props.settings.defaults?.default_sla_hours ?? 24,
  ticket_numbering_format: props.settings.defaults?.ticket_numbering_format ?? 'TIC-{YYYY}-{####}',
  task_numbering_format: props.settings.defaults?.task_numbering_format ?? 'TSK-{YYYY}-{####}',
  project_numbering_format: props.settings.defaults?.project_numbering_format ?? 'PRJ-{YYYY}-{####}',
  auto_assign_enabled: props.settings.defaults?.auto_assign_enabled ?? false,
  auto_assign_role: props.settings.defaults?.auto_assign_role ?? '',
  auto_assign_strategy: props.settings.defaults?.auto_assign_strategy ?? 'round_robin',
});

const defaultsInitial = ref(formSnapshot(defaultsForm));
const defaultsDirty = computed(() => JSON.stringify(formSnapshot(defaultsForm)) !== JSON.stringify(defaultsInitial.value));

const saveDefaults = () => {
  defaultsForm.post(resolveRoute('settings.defaults.update'), {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      defaultsInitial.value = formSnapshot(defaultsForm);
      pushToast('success', 'Defaults saved.');
    },
    onError: () => {
      pushToast('error', 'Failed to save defaults.');
    },
  });
};

const resetDefaults = () => {
  defaultsForm.reset();
  defaultsForm.clearErrors();
  defaultsInitial.value = formSnapshot(defaultsForm);
};

const roleRows = ref((roleMatrix.value.roles ?? []).map(role => ({
  ...role,
  permissions: [...(role.permissions || [])],
})));
const exportRoleOptions = computed(() => [
  { label: 'All', value: '' },
  ...(roleRows.value ?? []).map(role => ({
    label: role?.label ?? role?.name ?? 'Role',
    value: role?.name ?? role?.id ?? '',
  })),
]);

const roleInitial = ref(JSON.stringify(roleRows.value.map(role => ({ id: role.id, permissions: role.permissions }))));
const rolesSaving = ref(false);
const rolesDirty = computed(() => JSON.stringify(roleRows.value.map(role => ({ id: role.id, permissions: role.permissions }))) !== roleInitial.value);

const togglePermission = (role, permission) => {
  if (role.is_builtin) return;
  if (role.permissions.includes(permission)) {
    role.permissions = role.permissions.filter(item => item !== permission);
  } else {
    role.permissions = [...role.permissions, permission];
  }
};

const saveRoles = () => {
  rolesSaving.value = true;
  router.post(resolveRoute('settings.roles.update'), { roles: roleRows.value.map(role => ({ id: role.id, permissions: role.permissions })) }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      roleInitial.value = JSON.stringify(roleRows.value.map(role => ({ id: role.id, permissions: role.permissions })));
      pushToast('success', 'Role permissions saved.');
    },
    onError: () => {
      pushToast('error', 'Failed to save role permissions.');
    },
    onFinish: () => {
      rolesSaving.value = false;
    },
  });
};

const resetRoles = () => {
  roleRows.value = (roleMatrix.value.roles ?? []).map(role => ({
    ...role,
    permissions: [...(role.permissions || [])],
  }));
  roleInitial.value = JSON.stringify(roleRows.value.map(role => ({ id: role.id, permissions: role.permissions })));
};

const impersonateUserId = ref('');
const startImpersonate = () => {
  if (!impersonateUserId.value) {
    pushToast('error', 'Provide a user id to impersonate.');
    return;
  }
  if (!features.value.impersonation || !securityForm.allow_impersonation) {
    pushToast('error', 'Impersonation is disabled by policy.');
    return;
  }
  router.post(resolveRoute('settings.impersonate.user', { user: impersonateUserId.value }), {}, {
    onSuccess: () => pushToast('success', 'Impersonation started.'),
    onError: () => pushToast('error', 'Failed to start impersonation.'),
  });
};

const stopImpersonate = () => {
  router.post(resolveRoute('settings.impersonate.stop'), {}, {
    onSuccess: () => pushToast('success', 'Impersonation stopped.'),
    onError: () => pushToast('error', 'Failed to stop impersonation.'),
  });
};

const formatISODate = date => date.toISOString().slice(0, 10);
const daysAgo = days => {
  const date = new Date();
  date.setDate(date.getDate() - days);
  return formatISODate(date);
};
const buildDateOptions = currentValue => {
  const base = [
    { label: 'Any time', value: '' },
    { label: 'Today', value: formatISODate(new Date()) },
    { label: 'Last 7 days', value: daysAgo(7) },
    { label: 'Last 30 days', value: daysAgo(30) },
    { label: 'Last 90 days', value: daysAgo(90) },
    { label: 'Custom date…', value: '__custom__' },
  ];
  if (currentValue && !base.some(option => option.value === currentValue)) {
    base.splice(1, 0, { label: `Custom: ${currentValue}`, value: currentValue });
  }
  return base;
};

const exportForm = ref({
  type: 'tickets',
  format: 'csv',
  q: '',
  status: '',
  role: '',
  from: '',
  to: '',
  limit: 1000,
});
const handleDateSelection = (field, value) => {
  if (value === '__custom__') {
    const input = typeof window !== 'undefined'
      ? window.prompt('Enter date (YYYY-MM-DD)', exportForm.value[field] || '')
      : null;
    if (input && /^\d{4}-\d{2}-\d{2}$/.test(input.trim())) {
      exportForm.value[field] = input.trim();
    }
    return;
  }
  exportForm.value[field] = value;
};
const exportFromOptions = computed(() => buildDateOptions(exportForm.value.from ?? ''));
const exportToOptions = computed(() => buildDateOptions(exportForm.value.to ?? ''));
const exportFromValue = computed({
  get: () => exportForm.value.from ?? '',
  set: value => handleDateSelection('from', value),
});
const exportToValue = computed({
  get: () => exportForm.value.to ?? '',
  set: value => handleDateSelection('to', value),
});

const runExport = () => {
  const params = new URLSearchParams();
  Object.entries(exportForm.value).forEach(([key, value]) => {
    if (value !== '' && value !== null && value !== undefined) {
      params.set(key, value);
    }
  });
  const exportUrl = new URL(resolveRoute('settings.export'), window.location.origin);
  params.forEach((value, key) => exportUrl.searchParams.set(key, value));
  window.location.href = exportUrl.toString();
};

const healthLoading = ref(true);
const healthError = ref('');
const healthItems = ref([]);

const loadHealth = async () => {
  healthLoading.value = true;
  healthError.value = '';
  try {
    const response = await fetch(resolveRoute('settings.health'));
    if (!response.ok) {
      throw new Error('Failed to load health info.');
    }
    const data = await response.json();
    healthItems.value = [
      { label: 'App', value: data.app_name },
      { label: 'Environment', value: data.environment },
      { label: 'Framework', value: data.framework_version },
      { label: 'PHP', value: data.php_version },
      { label: 'Queue', value: data.queue_connection },
      { label: 'Cache', value: data.cache_driver },
    ];
  } catch (error) {
    healthError.value = error.message || 'Unable to load health info.';
  } finally {
    healthLoading.value = false;
  }
};

const systemActionLoading = ref(false);
const systemPolicy = computed(() => ({
  isProduction: features.value.environment === 'production',
  allowProduction: features.value.system_actions_in_production,
}));
const systemActionsDisabled = computed(() => systemPolicy.value.isProduction && !systemPolicy.value.allowProduction);

const confirmDialog = ref({
  open: false,
  title: '',
  message: '',
  confirm: null,
});

const openConfirm = (title, message, confirm) => {
  confirmDialog.value = { open: true, title, message, confirm };
};

const closeConfirm = () => {
  confirmDialog.value = { open: false, title: '', message: '', confirm: null };
};

const runConfirmDialog = () => {
  const fn = confirmDialog.value.confirm;
  closeConfirm();
  if (typeof fn === 'function') {
    fn();
  }
};

const confirmSystemAction = action => {
  const firstTitle = action === 'clear' ? 'Clear caches?' : 'Rebuild indexes?';
  const firstMessage = action === 'clear'
    ? 'This will clear application caches.'
    : 'This will rebuild indexes and may take time.';

  const secondTitle = action === 'clear' ? 'Confirm cache clear' : 'Confirm index rebuild';
  const secondMessage = 'Step 2 of 2: confirm this action for production.';

  const runConfirmed = () => runSystemAction(action, systemPolicy.value.isProduction);

  if (systemPolicy.value.isProduction) {
    openConfirm(firstTitle, firstMessage, () => {
      openConfirm(secondTitle, secondMessage, runConfirmed);
    });
    return;
  }

  openConfirm(firstTitle, firstMessage, runConfirmed);
};

const runSystemAction = async (action, confirmed = false) => {
  closeConfirm();
  systemActionLoading.value = true;
  try {
    const endpoint = action === 'clear'
      ? resolveRoute('settings.system.clear-cache')
      : resolveRoute('settings.system.rebuild-index');
    const response = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ confirmed }),
    });
    const payload = await response.json();
    if (!response.ok) {
      throw new Error(payload.message || 'System action failed.');
    }
    pushToast('success', payload.message || 'System action completed.');
  } catch (error) {
    pushToast('error', error.message || 'System action failed.');
  } finally {
    systemActionLoading.value = false;
  }
};

const auditRows = ref([]);
const auditMeta = ref({ current_page: 1, last_page: 1 });
const auditLoading = ref(true);
const auditError = ref('');
const auditQuery = ref('');

const fetchAudit = async (pageNum = 1) => {
  auditLoading.value = true;
  auditError.value = '';
  try {
    const params = new URLSearchParams({ page: String(pageNum) });
    if (auditQuery.value) {
      params.set('q', auditQuery.value);
    }
    const response = await fetch(`${resolveRoute('settings.audit')}?${params.toString()}`);
    if (!response.ok) {
      throw new Error('Failed to load audit log.');
    }
    const data = await response.json();
    auditRows.value = data.data || [];
    auditMeta.value = data.meta || { current_page: 1, last_page: 1 };
  } catch (error) {
    auditError.value = error.message || 'Unable to load audit log.';
  } finally {
    auditLoading.value = false;
  }
};

const formatDate = value => {
  if (!value) return '-';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleString();
};

const toasts = ref([]);
const pushToast = (type, message) => {
  const id = `${Date.now()}-${Math.random()}`;
  toasts.value.push({ id, type, message });
  setTimeout(() => {
    toasts.value = toasts.value.filter(toast => toast.id !== id);
  }, 4000);
};

watch(
  () => page.props.flash,
  value => {
    if (!value) return;
    if (value.success) pushToast('success', value.success);
    if (value.error) pushToast('error', value.error);
  }
);

onMounted(() => {
  loadHealth();
  fetchAudit();
});
</script>

<style scoped>
.form-input {
  width: 100%;
  border-radius: 0.75rem;
  border: 1px solid rgba(148, 163, 184, 0.4);
  background: #fff;
  padding: 0.6rem 0.75rem;
  font-size: 0.875rem;
  color: #0f172a;
}

.dark .form-input {
  border-color: rgba(71, 85, 105, 0.7);
  background: rgba(15, 23, 42, 0.6);
  color: #e2e8f0;
}

.form-error {
  margin-top: 0.35rem;
  font-size: 0.75rem;
  color: #ef4444;
}

.btn-primary {
  border-radius: 999px;
  background: #2563eb;
  padding: 0.55rem 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #fff;
  transition: all 0.2s ease;
}

.btn-primary:hover {
  background: #1d4ed8;
}

.btn-primary:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  border-radius: 999px;
  border: 1px solid #cbd5f5;
  padding: 0.55rem 1.25rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #1e40af;
  background: #eff6ff;
}

.btn-ghost {
  border-radius: 999px;
  border: 1px solid rgba(148, 163, 184, 0.3);
  padding: 0.5rem 1.1rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #475569;
}

.toast {
  pointer-events: auto;
  min-width: 220px;
  border-radius: 0.9rem;
  padding: 0.75rem 1rem;
  font-size: 0.85rem;
  font-weight: 600;
  color: #0f172a;
  background: #f1f5f9;
  box-shadow: 0 12px 24px -16px rgba(15, 23, 42, 0.4);
}

.toast.success {
  background: #dcfce7;
  color: #166534;
}

.toast.error {
  background: #fee2e2;
  color: #b91c1c;
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.25s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateY(8px);
}
</style>
