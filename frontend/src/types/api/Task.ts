import ModuleType from '@/types/enum/ModuleType';
import TaskStates from '@/types/enum/TaskStates';

export interface Task {
  id: string;
  // see: https://www.typescriptlang.org/docs/handbook/enums.html
  taskType: keyof typeof ModuleType;
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