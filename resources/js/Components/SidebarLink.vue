<template>
  <button type="button" class="nav-link" :class="{ 'nav-link--active': active }" @click="navigate">
    <span class="material-icons">{{ item.icon }}</span>

    <div v-if="sidebarOpen" class="nav-main">
      <span class="nav-label">{{ item.label }}</span>
      <span v-if="item.badge !== undefined" class="nav-badge">{{ item.badge }}</span>
    </div>

    <span v-else-if="item.badge !== undefined" class="nav-dot"></span>
  </button>
</template>

<script setup>
const props = defineProps({
  item: {
    type: Object,
    required: true,
  },
  active: {
    type: Boolean,
    default: false,
  },
  sidebarOpen: {
    type: Boolean,
    default: true,
  },
})

const emit = defineEmits(['navigate'])

const navigate = () => {
  emit('navigate', props.item.href)
}
</script>

<style scoped>
.nav-link {
  width: 100%;
  display: inline-flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.6rem 0.9rem;
  border-radius: 0.9rem;
  color: inherit;
  background: transparent;
  border: none;
  font-weight: 600;
  text-align: left;
  transition: background 0.2s ease, color 0.2s ease;
}


.nav-main {
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  flex: 1 1 auto;
}

.nav-link:hover {
  background: var(--sidebar-active-bg, #2563eb);
  color: #ffffff;
  box-shadow: 0 16px 36px -24px rgba(37, 99, 235, 0.55);
}

.nav-link--active {
  background: var(--sidebar-active-bg, #2563eb);
  color: #ffffff;
  box-shadow: 0 12px 32px -18px rgba(37, 99, 235, 0.55);
}

.nav-link:hover .material-icons,
.nav-link:hover .nav-label,
.nav-link:hover .nav-badge,
.nav-link--active .material-icons,
.nav-link--active .nav-label,
.nav-link--active .nav-badge {
  color: #ffffff;
}

.dark .nav-link:hover,
.dark .nav-link--active {
  color: #e0f2ff;
}

.nav-label {
  white-space: nowrap;
}

.nav-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.8rem;
  padding: 0 0.45rem;
  border-radius: 999px;
  font-size: 0.72rem;
  font-weight: 600;
  color: #ffffff;
  background: var(--sidebar-active-bg, #2563eb);
  box-shadow: 0 12px 28px -18px rgba(37, 99, 235, 0.45);
}

.dark .nav-badge {
  color: #e0f2ff;
  background: linear-gradient(135deg, rgba(59, 130, 246, 0.9), rgba(99, 102, 241, 0.95));
}

.nav-dot {
  width: 0.52rem;
  height: 0.52rem;
  border-radius: 50%;
  margin-left: auto;
  background: rgba(37, 99, 235, 0.45);
}
</style>
