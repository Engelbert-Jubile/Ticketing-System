<template>
  <div class="rich-text-quill" ref="container"></div>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Tulis deskripsi di sini…' },
  toolbar: {
    type: Array,
    default: () => [
      [{ header: [1, 2, 3, false] }],
      ['bold', 'italic', 'underline', 'strike', 'blockquote'],
      [{ list: 'ordered' }, { list: 'bullet' }],
      [{ align: [] }],
      ['link', 'code-block'],
      ['clean'],
    ],
  },
});

const emit = defineEmits(['update:modelValue', 'blur']);

const container = ref(null);
let quillInstance = null;

const ensureValue = value => (value == null || value === '' ? '' : value);

onMounted(async () => {
  const [{ default: Quill }] = await Promise.all([
    import('quill'),
    import('quill/dist/quill.snow.css'),
  ]);

  quillInstance = new Quill(container.value, {
    theme: 'snow',
    placeholder: props.placeholder,
    modules: {
      toolbar: props.toolbar,
    },
  });

  if (props.modelValue) {
    quillInstance.clipboard.dangerouslyPasteHTML(props.modelValue);
  }

  quillInstance.on('text-change', () => {
    const html = quillInstance.root.innerHTML;
    emit('update:modelValue', html === '<p><br></p>' ? '' : html);
  });

  quillInstance.on('selection-change', range => {
    if (!range) {
      emit('blur');
    }
  });
});

watch(
  () => props.modelValue,
  value => {
    if (!quillInstance) return;
    const current = quillInstance.root.innerHTML;
    const incoming = ensureValue(value);
    if (incoming !== current) {
      const selection = quillInstance.getSelection();
      quillInstance.root.innerHTML = incoming;
      if (selection) {
        quillInstance.setSelection(selection);
      }
    }
  }
);

onBeforeUnmount(() => {
  if (quillInstance) {
    quillInstance.off('text-change');
    quillInstance.off('selection-change');
    quillInstance = null;
  }
});
</script>

<style scoped>
.rich-text-quill :deep(.ql-toolbar) {
  border-radius: 0.75rem 0.75rem 0 0;
  border-color: rgba(148, 163, 184, 0.35);
}

.rich-text-quill :deep(.ql-container) {
  min-height: 180px;
  border-radius: 0 0 0.75rem 0.75rem;
  border-color: rgba(148, 163, 184, 0.35);
  font-size: 0.95rem;
}

.dark .rich-text-quill :deep(.ql-toolbar),
.dark .rich-text-quill :deep(.ql-container) {
  border-color: rgba(71, 85, 105, 0.6);
}
</style>
