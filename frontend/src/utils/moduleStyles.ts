import TaskType from '@/types/enum/TaskType';
import TaskTypeColor from '@/types/TaskTypeColor';

export const setModuleStyles = (type: TaskType, ref: HTMLElement | null = null): void => {
  if (!ref) ref = document.getElementsByTagName("body")[0];
  ref.style.setProperty('--module-color', TaskTypeColor[type]);
  ref.style.setProperty('--module-planet', `/assets/illustrations/${type}.png`);
};
