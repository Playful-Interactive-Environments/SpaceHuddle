import TaskType from '@/types/enum/TaskType';
import TaskStates from '@/types/enum/TaskStates';
import { Module } from '@/types/api/Module';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface Task {
  id: string;
  topicId: string;
  sessionId: string;
  // see: https://www.typescriptlang.org/docs/handbook/enums.html
  taskType: keyof typeof TaskType;
  name: string;
  description: string;
  parameter: any;
  order: number;
  state: TaskStates;
  remainingTime: number | null;
  activeOnParticipant: boolean;
  syncPublicParticipant: boolean;
  modules: Module[];
  participantCount: number;
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
  remainingTime: number | null;
  modules: string[];
}

export interface TaskSettingsData {
  taskType: keyof typeof TaskType;
  name: string;
  description: string;
  parameter: any;
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
    remainingTime: task.remainingTime,
  } as TaskForSaveAction;
};
