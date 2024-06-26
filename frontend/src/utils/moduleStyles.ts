import TaskType from '@/types/enum/TaskType';
import { getColorOfType } from '@/types/enum/TaskCategory';

export const setModuleStyles = (
  type: TaskType,
  ref: HTMLElement | null = null
): void => {
  const defaultColor = 'var(--color-brainstorming)';
  let color: string | undefined = defaultColor;
  if (type) color = getColorOfType(type);
  if (!color) color = defaultColor;
  if (!ref) ref = document.getElementsByTagName('body')[0];
  ref.style.setProperty('--module-color', color);
};
