<template>
  <span class="assignee-pill">
    <span v-if="initial" class="assignee-pill__avatar">{{ initial }}</span>
    <span class="assignee-pill__content">
      <span v-if="role" class="assignee-pill__role">{{ role }}</span>
      <span class="assignee-pill__name">{{ name }}</span>
    </span>
  </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  name: { type: String, required: true },
  email: { type: String, default: '' },
  role: { type: String, default: '' },
});

const initial = computed(() => {
  const base = (props.name || props.email || '').trim();
  return base ? base.charAt(0).toUpperCase() : '';
});

const role = computed(() => props.role || '');
</script>

<style scoped>
.assignee-pill {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  border-radius: 9999px;
  background-color: rgba(99, 102, 241, 0.12);
  color: #4338ca;
  font-size: 0.75rem;
  font-weight: 600;
  padding: 0.25rem 0.6rem;
}

.assignee-pill__avatar {
  display: inline-flex;
  width: 1.5rem;
  height: 1.5rem;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  background-color: rgba(99, 102, 241, 0.22);
  color: inherit;
  font-size: 0.7rem;
}

.assignee-pill__content {
  display: flex;
  flex-direction: column;
  line-height: 1.1;
}

.assignee-pill__role {
  font-size: 0.6rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  opacity: 0.8;
}

.assignee-pill__name {
  white-space: nowrap;
}

.dark .assignee-pill {
  background-color: rgba(129, 140, 248, 0.16);
  color: #c7d2fe;
}

.dark .assignee-pill__avatar {
  background-color: rgba(99, 102, 241, 0.35);
}
</style>
