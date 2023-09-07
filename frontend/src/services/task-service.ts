import {
  apiExecuteDelete,
  apiExecutePost,
  apiExecutePostHandled,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Task, TaskForSaveAction } from '@/types/api/Task';
import * as cashService from '@/services/cash-service';
import { Topic } from '@/types/api/Topic';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const registerGetTaskById = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Task> => {
  return cashService.registerSimplifiedGet<Task>(
    `/${EndpointType.TASK}/${taskId}/`,
    callback,
    {},
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetTaskById = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(`/${EndpointType.TASK}/${taskId}/`, callback);
};

export const deleteTask = async (id: string): Promise<boolean> => {
  return await apiExecuteDelete<any>(`/${EndpointType.TASK}/${id}/`);
};

export const registerGetTaskList = (
  topicId: string,
  callback: (result: Task[], topicId: string) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Task[]> => {
  return cashService.registerSimplifiedGet<Task[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.TASKS}/`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (result: Task[]) => {
      if (result && Array.isArray(result)) return result;
      return [];
    },
    [topicId]
  );
};

export const deregisterGetTaskList = (
  topicId: string,
  callback: (result: Task[], topicId: string) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.TASKS}/`,
    callback
  );
};

export const refreshGetTaskList = (topicId: string): void => {
  cashService.refreshCash(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.TASKS}/`
  );
};

export const registerGetDependentTaskList = (
  taskId: string,
  callback: (result: any, taskId: string) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Task[]> => {
  return cashService.registerSimplifiedGet<Task[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.DEPENDENT}/`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    null,
    [taskId]
  );
};

export const deregisterGetDependentTaskList = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.DEPENDENT}/`,
    callback
  );
};

export const postTask = async (
  topicId: string,
  data: Partial<TaskForSaveAction>
): Promise<Task> => {
  return await apiExecutePost<Task>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.TASK}/`,
    data
  );
};

export const putTask = async (
  data: Partial<TaskForSaveAction>
): Promise<Task> => {
  return await apiExecutePut<Task>(
    `/${EndpointType.TASK}/`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const clone = async (
  taskId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Topic> => {
  return apiExecutePostHandled<Topic>(
    `/${EndpointType.TASK}/${taskId}/clone`,
    null,
    null,
    authHeaderType
  );
};
