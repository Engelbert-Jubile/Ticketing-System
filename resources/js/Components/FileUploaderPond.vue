<template>
  <input ref="inputRef" type="file" />
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { route as ziggyRoute } from 'ziggy-js';

const props = defineProps({
  processUrl: { type: String, default: '' },
  revertUrl: { type: String, default: '' },
  multiple: { type: Boolean, default: false },
  acceptedFileTypes: {
    type: Array,
    default: () => [
      'application/pdf',
      'image/png',
      'image/jpeg',
      'application/msword',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
      'application/vnd.ms-excel',
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'application/zip',
      'application/x-rar-compressed',
      'text/plain',
      'text/csv',
    ],
  },
  allowedExtensions: {
    type: Array,
    default: () => [],
  },
  meta: {
    type: Object,
    default: () => ({}),
  },
  disabled: {
    type: Boolean,
    default: false,
  },
});

const resolveRoute = (name, params = {}) => {
  try {
    return ziggyRoute(name, params, false);
  } catch (_) {
    if (typeof window !== 'undefined' && typeof window.route === 'function') {
      return window.route(name, params, false);
    }
    return '#';
  }
};

const detectLocalePrefix = () => {
  if (typeof window === 'undefined') return '';
  const match = window.location.pathname.match(/^\/(en|id)\b/);
  return match ? `/${match[1]}` : '';
};

const normalizeEndpoint = (value, fallback) => {
  if (typeof value !== 'string' || value.trim() === '' || value.trim() === '#') {
    return fallback;
  }
  return value;
};

const localePrefix = detectLocalePrefix();
const defaultProcess = normalizeEndpoint(resolveRoute('attachments.process'), `${localePrefix}/dashboard/attachments`);
const defaultRevert = normalizeEndpoint(resolveRoute('attachments.revert'), `${localePrefix}/dashboard/attachments/revert`);

const processEndpoint = computed(() => normalizeEndpoint(props.processUrl, defaultProcess));
const revertEndpoint = computed(() => normalizeEndpoint(props.revertUrl, defaultRevert));

const emit = defineEmits(['uploaded', 'removed', 'error']);

const inputRef = ref(null);
let pondInstance = null;

const disableInstance = state => {
  if (!pondInstance) {
    return;
  }
  pondInstance.setOptions({
    disabled: Boolean(state),
    allowBrowse: !state,
    allowDrop: !state,
    allowPaste: !state,
  });
};

const updateAcceptedTypes = types => {
  if (!pondInstance) {
    return;
  }
  const normalized = Array.isArray(types) ? types.filter(Boolean) : [];
  pondInstance.setOptions({ acceptedFileTypes: normalized.length ? normalized : null });
};

const resetFiles = () => {
  if (pondInstance) {
    pondInstance.removeFiles();
  }
};

defineExpose({ reset: resetFiles });

onMounted(async () => {
  const token = document.head.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

  const [pondModule, validateTypeModule, validateSizeModule] = await Promise.all([
    import('filepond'),
    import('filepond-plugin-file-validate-type'),
    import('filepond-plugin-file-validate-size'),
    import('filepond/dist/filepond.min.css'),
  ]);

  const FilePondGlobal = pondModule.default ?? pondModule;
  const registerPlugin = FilePondGlobal.registerPlugin ?? pondModule.registerPlugin;
  const create = FilePondGlobal.create ?? pondModule.create ?? FilePondGlobal;

  const validateType = validateTypeModule.default ?? validateTypeModule;
  const validateSize = validateSizeModule.default ?? validateSizeModule;

  if (typeof registerPlugin === 'function') {
    registerPlugin(validateType, validateSize);
  }

  pondInstance = create(inputRef.value, {
    name: 'file',
    allowMultiple: props.multiple,
    maxFiles: props.multiple ? null : 1,
    acceptedFileTypes: props.acceptedFileTypes,
    allowBrowse: !props.disabled,
    allowDrop: !props.disabled,
    allowPaste: !props.disabled,
    server: {
      process: {
        url: processEndpoint.value,
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': token,
        },
        withCredentials: false,
        ondata: formData => {
          if (Array.isArray(props.allowedExtensions) && props.allowedExtensions.length > 0) {
            formData.set(
              'allowed_extensions',
              props.allowedExtensions
                .map(value => String(value).trim().toLowerCase())
                .filter(Boolean)
                .join(',')
            );
          }

          Object.entries(props.meta ?? {}).forEach(([key, value]) => {
            if (value === undefined || value === null) {
              return;
            }
            formData.set(key, value);
          });

          return formData;
        },
        onload: response => {
          try {
            const payload = JSON.parse(response);
            emit('uploaded', payload);
            return payload.id ?? response;
          } catch (error) {
            emit('uploaded', { id: response });
            return response;
          }
        },
        onerror: response => {
          emit('error', response);
        },
      },
      revert: {
        url: revertEndpoint.value,
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': token,
        },
      },
    },
  });

  pondInstance.on('processfile', (error, file) => {
    if (error) {
      emit('error', error);
    }
  });

  pondInstance.on('removefile', (_, file) => {
    const serverId = file.serverId || file.source;
    if (serverId) {
      emit('removed', serverId);
    }
  });

  disableInstance(props.disabled);
});

onBeforeUnmount(() => {
  if (pondInstance) {
    pondInstance.destroy();
    pondInstance = null;
  }
});

watch(
  () => props.acceptedFileTypes,
  value => {
    updateAcceptedTypes(value);
  },
  { deep: true }
);

watch(
  () => props.disabled,
  value => {
    disableInstance(value);
  }
);
</script>

<style scoped>
input[type='file'] {
  display: none;
}
</style>
