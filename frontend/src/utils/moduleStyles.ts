import TaskType from '@/types/enum/TaskType';
import ModuleColors from '@/types/ModuleColors';

export const setModuleStyles = (ref: HTMLElement, type: TaskType): void => {
  ref.style.setProperty('--module-color', ModuleColors[type]);
  ref.style.setProperty('--module-planet', `/assets/illustrations/${type}.png`);
};
