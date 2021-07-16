import {apiEndpoint, apiExecuteGet, apiExecuteGetHandled, apiExecutePost, apiExecutePut} from '@/services/api';
import ModuleType from '@/types/ModuleType';
import TaskStates from '@/types/TaskStates';
import EndpointType from '@/types/Endpoint';
import { Idea } from './idea-service';

export interface Task {
  id: string;
  // see: https://www.typescriptlang.org/docs/handbook/enums.html
  taskType: keyof typeof ModuleType;
  name: string;
  description: string;
  parameter: any;
  order: number;
  state: TaskStates;
}

export const getTaskById = async (taskId: string): Promise<Task> => {
  return await apiExecuteGetHandled<Task>(
    `/${EndpointType.TASK}/${taskId}/`
  );
};

export const updateTask = async (data: Task): Promise<Task> => {
  return await apiExecutePut<Task>(
    `/${EndpointType.TASK}/`,
    data
  );
};

export const getTaskList = async (topicId: string): Promise<Task[]> => {
  return await apiExecuteGetHandled<Task[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.TASKS}/`,
    []
  );
};

export const postTask = async (
  taskId: string,
  data: Partial<Task>
): Promise<Task> => {
  return await apiExecutePost<Task>(
    `/${EndpointType.TOPIC}/${taskId}/${EndpointType.TASK}/`,
    data
  );
};
