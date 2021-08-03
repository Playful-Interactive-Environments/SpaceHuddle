import config from './config.json';
import TaskType from '@/types/enum/TaskType';

export const getModulesForTaskType = (taskType: keyof typeof TaskType): string[] => {
  const taskTypeName = TaskType[taskType];
  const modules = (config as any)[taskTypeName];
  return Object.keys(modules) as string[];
};
