import { apiEndpoint } from '@/services/api';
import ModuleType from '@/types/ModuleType';
import TaskStates from '@/types/TaskStates';
import EndpointType from '@/types/Endpoint';
import { Idea } from './idea-service';

export interface Task {
  id: string;
  // see: https://www.typescriptlang.org/docs/handbook/enums.html
  taskType: keyof typeof ModuleType;
  name: string;
  parameter: any; // TODO: ask what options can be provided
  order: number;
  state: TaskStates; // TODO: ask what possible states a task can be in - WAIT,
}

const API_TASK_ENDPOINT = apiEndpoint(EndpointType.TASK);

export const getTaskById = async (taskId: string): Promise<Task> => {
  const { data } = await API_TASK_ENDPOINT.get<Task>(`/${taskId}`);
  return data;
};

export const getIdeasForTask = async (taskId: string): Promise<Idea[]> => {
  const { data } = await API_TASK_ENDPOINT.get<Idea[]>(
    `/${taskId}/${EndpointType.IDEAS}`
  );
  return data;
};

export const updateTask = async (task: Task): Promise<Task> => {
  const { data } = await API_TASK_ENDPOINT.put<Task>(``, task);
  return data;
};
