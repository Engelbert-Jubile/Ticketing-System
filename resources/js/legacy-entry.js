import './legacy';
import './attachmentPreview';
import '../css/glassy.css';
import { applyThemePreferences } from './utils/appearance';

applyThemePreferences(window.tickoraTheme?.preferences);
document.addEventListener('livewire:navigated', () => applyThemePreferences(window.tickoraTheme?.preferences));
