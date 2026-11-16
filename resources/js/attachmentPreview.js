import { createApp, reactive } from 'vue';
import '../css/attachment-preview.css';

// One delegated handler also covers attachment links added by Inertia or Livewire.
const state = reactive({ attachment: null });
let mounted = false;
let opening = 0;

document.addEventListener('click', async event => {
  const link = event.target.closest?.('a[href]');
  if (!link || event.defaultPrevented || event.button !== 0) return;
  const url = new URL(link.href, window.location.href);
  if (url.origin !== window.location.origin || !/\/dashboard\/attachments\/[^/]+\/view\/?$/.test(url.pathname)) return;

  event.preventDefault();
  const current = ++opening;
  if (!mounted) {
    try {
      const { default: AttachmentPreview } = await import('./Components/AttachmentPreview.vue');
      if (current !== opening) return;
      const host = document.createElement('div');
      host.id = 'attachment-preview-root';
      document.body.append(host);
      createApp(AttachmentPreview, { state }).mount(host);
      mounted = true;
    } catch {
      // Keep failures in this page; never fall back to a new tab.
      window.alert(document.documentElement.lang === 'id'
        ? 'Preview gagal dimuat. Silakan muat ulang halaman atau unduh file.'
        : 'Unable to load preview. Please reload this page or download the file.');
      return;
    }
  }
  state.attachment = {
    viewUrl: url.href,
    downloadUrl: url.href.replace(/\/view\/?(?=[?#]|$)/, '/download'),
    locale: document.documentElement.lang,
    trigger: link,
  };
}, true);

const closePreview = () => { opening++; state.attachment = null; };
document.addEventListener('inertia:start', closePreview);
document.addEventListener('livewire:navigating', closePreview);
window.addEventListener('pagehide', closePreview);
