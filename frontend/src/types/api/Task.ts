import TaskType from '@/types/enum/TaskType';
import TaskStates from '@/types/enum/TaskStates';
import { Module } from '@/types/api/Module';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface Dependency {
  start: number;
  duration: number;
}

export interface Task {
  id: string;
  topicId: string;
  topicOrder: number;
  sessionId: string;
  // see: https://www.typescriptlang.org/docs/handbook/enums.html
  taskType: keyof typeof TaskType;
  name: string;
  description: string;
  keywords: string;
  parameter: any;
  order: number;
  state: TaskStates;
  remainingTime: number | null;
  activeOnParticipant: boolean;
  syncPublicParticipant: boolean;
  modules: Module[];
  participantCount: number;
  dependency: Dependency;
}

export interface TaskForSaveAction {
  id: string;
  // see: https://www.typescriptlang.org/docs/handbook/enums.html
  taskType: keyof typeof TaskType;
  name: string;
  description: string;
  keywords: string;
  parameter: any;
  order: number;
  state: TaskStates;
  remainingTime: number | null;
  modules: string[];
  dependency: Dependency;
}

export interface TaskSettingsData {
  taskType: keyof typeof TaskType;
  name: string;
  description: string;
  keywords: string;
  parameter: any;
}

export const convertToSaveVersion = (task: Task): TaskForSaveAction => {
  return {
    id: task.id,
    taskType: task.taskType,
    name: task.name,
    description: task.description,
    keywords: task.keywords,
    parameter: task.parameter,
    order: task.order,
    state: task.state,
    dependency: task.dependency,
    remainingTime: task.remainingTime,
  } as TaskForSaveAction;
};
