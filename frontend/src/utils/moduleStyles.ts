import ModuleType from '@/types/enum/ModuleType';
import ModuleColors from '@/types/ModuleColors';

export const setModuleStyles = (ref: HTMLElement, type: ModuleType): void => {
  ref.style.setProperty('--module-color', ModuleColors[type]);
  ref.style.setProperty('--module-planet', `/assets/illustrations/${type}.png`);
};
