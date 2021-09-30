import TaskType from '@/types/enum/TaskType';
import TaskTypeColor from '@/types/TaskTypeColor';

export const setModuleStyles = (ref: HTMLElement, type: TaskType): void => {
  ref.style.setProperty('--module-color', TaskTypeColor[type]);
  ref.style.setProperty('--module-planet', `/assets/illustrations/${type}.png`);
};
