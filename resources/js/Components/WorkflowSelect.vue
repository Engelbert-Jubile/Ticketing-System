<template>
  <div ref="anchor" class="relative w-full">
    <button ref="trigger" type="button" role="combobox" aria-haspopup="listbox"
      :aria-expanded="open" :aria-controls="listId" :aria-activedescendant="open && active >= 0 ? optionId(active) : undefined"
      :aria-label="ariaLabel" :disabled="disabled || loading" class="workflow-select-trigger" @click="toggle" @keydown="onKeydown">
      <span class="truncate" :class="selected ? '' : 'text-slate-400'">{{ displayLabel }}</span>
      <span v-if="loading" class="workflow-select-spinner" aria-hidden="true"></span>
      <span v-else class="material-icons text-lg text-slate-400 transition-transform" :class="open && 'rotate-180'">expand_more</span>
    </button>
    <Teleport to="body">
      <Transition name="workflow-select-fade">
        <div v-if="open" ref="panel" :id="listId" role="listbox" class="workflow-select-panel" :style="panelStyle">
          <div v-if="loading" class="workflow-select-message"><span class="workflow-select-spinner"></span>{{ loadingText }}</div>
          <div v-else-if="!options.length" class="workflow-select-message">{{ emptyText }}</div>
          <button v-for="(option, index) in options" v-else :id="optionId(index)" :key="option.value" type="button" role="option"
            :aria-selected="String(option.value) === String(modelValue)" :aria-disabled="option.disabled || undefined" :disabled="option.disabled"
            class="workflow-select-option" :class="optionClass(option, index)" @mouseenter="active = index" @click="choose(option)">
            <span class="truncate">{{ option.label }}</span><span v-if="String(option.value) === String(modelValue)" class="material-icons text-base">check</span>
          </button>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
const props = defineProps({ modelValue: { type: [String, Number, null], default: null }, options: { type: Array, default: () => [] }, placeholder: { type: String, default: 'Pilih opsi' }, ariaLabel: { type: String, default: 'Pilih opsi' }, disabled: Boolean, loading: Boolean, loadingText: { type: String, default: 'Memuat opsi...' }, emptyText: { type: String, default: 'Tidak ada opsi tersedia' } })
const emit = defineEmits(['update:modelValue'])
const anchor = ref(null), trigger = ref(null), panel = ref(null), open = ref(false), active = ref(-1), panelStyle = ref({})
const listId = `workflow-select-${Math.random().toString(36).slice(2)}`
const selected = computed(() => props.options.find(option => String(option.value) === String(props.modelValue)))
const displayLabel = computed(() => props.loading ? props.loadingText : selected.value?.label ?? props.placeholder)
const optionId = index => `${listId}-option-${index}`
const optionClass = (option, index) => ({ 'is-selected': String(option.value) === String(props.modelValue), 'is-active': index === active.value })
const enabled = () => props.options.map((option, index) => option.disabled ? -1 : index).filter(index => index >= 0)
const reposition = () => { if (!open.value || !trigger.value) return; const rect = trigger.value.getBoundingClientRect(); const max = Math.min(280, Math.max(64, window.innerHeight - 24)); const below = window.innerHeight - rect.bottom; const height = Math.min(max, props.options.length * 44 + 16); const upwards = below < height + 8 && rect.top > below; panelStyle.value = { position: 'fixed', left: `${rect.left}px`, width: `${rect.width}px`, maxHeight: `${Math.min(height, (upwards ? rect.top : below) - 8)}px`, top: upwards ? 'auto' : `${rect.bottom + 6}px`, bottom: upwards ? `${window.innerHeight - rect.top + 6}px` : 'auto' } }
const openMenu = () => { if (props.disabled || props.loading) return; open.value = true; active.value = props.options.findIndex(option => !option.disabled && String(option.value) === String(props.modelValue)); if (active.value < 0) active.value = enabled()[0] ?? -1; nextTick(reposition) }
const close = () => { open.value = false; active.value = -1 }
const toggle = () => open.value ? close() : openMenu()
const choose = option => { if (option.disabled) return; emit('update:modelValue', option.value); close(); nextTick(() => trigger.value?.focus()) }
const move = step => { const indexes = enabled(); if (!indexes.length) return; const current = indexes.indexOf(active.value); active.value = indexes[(Math.max(0, current) + step + indexes.length) % indexes.length]; nextTick(() => document.getElementById(optionId(active.value))?.scrollIntoView({ block: 'nearest' })) }
const onKeydown = event => { if (event.key === 'Escape' || event.key === 'Tab') return close(); if (['ArrowDown', 'ArrowUp', 'Home', 'End', 'Enter', ' '].includes(event.key)) event.preventDefault(); if (!open.value && ['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(event.key)) return openMenu(); if (event.key === 'ArrowDown') move(1); else if (event.key === 'ArrowUp') move(-1); else if (event.key === 'Home') active.value = enabled()[0] ?? -1; else if (event.key === 'End') active.value = enabled().at(-1) ?? -1; else if ((event.key === 'Enter' || event.key === ' ') && active.value >= 0) choose(props.options[active.value]) }
const outside = event => { if (!anchor.value?.contains(event.target) && !panel.value?.contains(event.target)) close() }
onMounted(() => { document.addEventListener('pointerdown', outside); window.addEventListener('resize', reposition); window.addEventListener('scroll', reposition, true) })
onBeforeUnmount(() => { document.removeEventListener('pointerdown', outside); window.removeEventListener('resize', reposition); window.removeEventListener('scroll', reposition, true) })
watch(() => props.options, () => { if (open.value) nextTick(reposition) })
</script>

<style scoped>
.workflow-select-trigger{display:flex;width:100%;min-height:2.75rem;align-items:center;justify-content:space-between;gap:.75rem;border:1px solid #cbd5e1;border-radius:.75rem;background:#fff;padding:.65rem .8rem;text-align:left;font-size:.875rem;color:#334155;transition:border-color .15s,box-shadow .15s}.workflow-select-trigger:hover:not(:disabled){border-color:#67e8f9}.workflow-select-trigger:focus-visible{border-color:#0891b2;outline:none;box-shadow:0 0 0 3px rgb(6 182 212/.18)}.workflow-select-trigger:disabled{cursor:not-allowed;opacity:.55}.workflow-select-panel{z-index:2147483002;overflow:auto;border:1px solid #bae6fd;border-radius:.85rem;background:#fff;padding:.4rem;box-shadow:0 18px 45px rgb(15 23 42/.18)}.workflow-select-option{display:flex;width:100%;align-items:center;justify-content:space-between;gap:.75rem;border-radius:.6rem;padding:.65rem .75rem;text-align:left;font-size:.85rem;color:#475569}.workflow-select-option:hover,.workflow-select-option.is-active{background:#ecfeff;color:#0e7490}.workflow-select-option.is-selected{background:#cffafe;color:#155e75;font-weight:700}.workflow-select-option:disabled{cursor:not-allowed;color:#cbd5e1}.workflow-select-message{display:flex;align-items:center;justify-content:center;gap:.5rem;padding:1rem;color:#94a3b8;font-size:.8rem}.workflow-select-spinner{height:1rem;width:1rem;border:2px solid #cbd5e1;border-top-color:#0891b2;border-radius:999px;animation:workflow-spin .8s linear infinite}.dark .workflow-select-trigger,.dark .workflow-select-panel{border-color:#475569;background:#0f172a;color:#e2e8f0}.dark .workflow-select-option{color:#cbd5e1}.dark .workflow-select-option:hover,.dark .workflow-select-option.is-active{background:rgb(8 145 178/.15);color:#a5f3fc}.dark .workflow-select-option.is-selected{background:rgb(6 182 212/.2);color:#cffafe}@keyframes workflow-spin{to{transform:rotate(360deg)}}.workflow-select-fade-enter-active,.workflow-select-fade-leave-active{transition:opacity .12s ease,transform .12s ease}.workflow-select-fade-enter-from,.workflow-select-fade-leave-to{opacity:0;transform:translateY(-3px)}
</style>
