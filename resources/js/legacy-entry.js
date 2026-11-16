import './legacy';
import './attachmentPreview';
import '../css/glassy.css';
import { applyAppearance, readAppearance } from './utils/appearance';

applyAppearance(readAppearance());
document.addEventListener('livewire:navigated', () => applyAppearance(readAppearance()));
