import TaskType from '@/types/enum/TaskType';
import TaskStates from '@/types/enum/TaskStates';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface Task {
  id: string;
  topicId: string;
  // see: https://www.typescriptlang.org/docs/handbook/enums.html
  taskType: keyof typeof TaskType;
  name: string;
  description: string;
  parameter: any;
  order: number;
  state: TaskStates;
  modules: {
    id: string;
    name: string;
    order: number;
    state: string;
    parameter: any;
  }[];
}

export interface TaskForSaveAction {
  id: string;
  // see: https://www.typescriptlang.org/docs/handbook/enums.html
  taskType: keyof typeof TaskType;
  name: string;
  description: string;
  parameter: any;
  order: number;
  state: TaskStates;
  modules: string[];
}

export const convertToSaveVersion = (task: Task): TaskForSaveAction => {
  return {
    id: task.id,
    taskType: task.taskType,
    name: task.name,
    description: task.description,
    parameter: task.parameter,
    order: task.order,
    state: task.state,
  } as TaskForSaveAction;
};
