import { apiEndpoint } from '@/services/api';
import ModuleType from '@/types/ModuleType';
import TaskStates from '@/types/TaskStates';
import EndpointType from '@/types/Endpoint';

export interface Task {
  id: string;
  taskType: ModuleType;
  name: string;
  parameter: any; // TODO: ask what options can be provided
  order: number;
  state: TaskStates; // TODO: ask what possible states a task can be in - WAIT,
}

export interface Idea {
  id: string;
  state: string; //what are the states of an idea??
  timestamp: string;
  description: string;
  keywords: string;
  image: string; //ignore at first?
  link: string; //link to where??
}

const API_TASK_ENDPOINT = apiEndpoint(EndpointType.TASK);
