import TaskType from '@/types/enum/TaskType';

export enum TaskCategoryOption {
  PLAYING = 'playing',
  INFORMING = 'informing',
  BRAINSTORMING = 'brainstorming',
  STRUCTURING = 'structuring',
  EVALUATING = 'evaluating',
}

//export default TaskCategory;
export interface TaskCategoryType {
  icon: string;
  color: string;
  taskTypes: TaskType[];
}

const TaskCategory: { [name: string]: TaskCategoryType } = {
  informing: {
    icon: 'info',
    color: 'var(--color-informing)',
    taskTypes: [TaskType.INFORMATION],
  },
  brainstorming: {
    icon: 'brain',
    color: 'var(--color-brainstorming)',
    taskTypes: [TaskType.BRAINSTORMING],
  },
  structuring: {
    icon: 'object-group',
    color: 'var(--color-structuring)',
    taskTypes: [TaskType.SELECTION, TaskType.CATEGORISATION],
  },
  evaluating: {
    icon: 'star',
    color: 'var(--color-evaluating)',
    taskTypes: [TaskType.VOTING],
  },
  playing: {
    icon: 'gamepad',
    color: 'var(--color-playing)',
    taskTypes: [TaskType.PLAYING],
  },
};

export default TaskCategory;

export const getCategoryOfType = (taskType: TaskType): string | undefined => {
  return Object.keys(TaskCategory).find((taskCategory) =>
    TaskCategory[taskCategory].taskTypes.includes(taskType)
  );
};

export const getColorOfType = (taskType: TaskType): string | undefined => {
  const taskCategoryName = getCategoryOfType(taskType);
  if (taskCategoryName) {
    const taskCategory: TaskCategoryType | undefined =
      TaskCategory[taskCategoryName];
    if (taskCategory) return taskCategory.color;
  }
};

export const getIconOfType = (taskType: TaskType): string | undefined => {
  const taskCategoryName = getCategoryOfType(taskType);
  if (taskCategoryName) {
    const taskCategory: TaskCategoryType | undefined =
      TaskCategory[taskCategoryName];
    if (taskCategory) return taskCategory.icon;
  }
};
