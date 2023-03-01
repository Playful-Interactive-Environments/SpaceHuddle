import {
  apiExecuteDelete,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Module } from '@/types/api/Module';
import * as cashService from '@/services/cash-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const registerGetModuleById = (
  moduleId: string,
  callback: (result: Module) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Module> => {
  return cashService.registerSimplifiedGet<Module>(
    `/${EndpointType.MODULE}/${moduleId}/`,
    callback,
    {},
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetModuleById = (
  moduleId: string,
  callback: (result: Module) => void
): void => {
  cashService.deregisterGet(`/${EndpointType.MODULE}/${moduleId}/`, callback);
};

export const deleteModule = async (id: string): Promise<boolean> => {
  return await apiExecuteDelete<any>(`/${EndpointType.MODULE}/${id}/`);
};

export const registerGetModuleList = (
  taskId: string,
  callback: (result: Module[]) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Module[]> => {
  return cashService.registerSimplifiedGet<Module[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.MODULES}/`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetModuleList = (
  taskId: string,
  callback: (result: Module[]) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.MODULES}/`,
    callback
  );
};

export const postModule = async (
  taskId: string,
  data: Partial<Module>
): Promise<Module> => {
  return await apiExecutePost<Module>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.MODULE}/`,
    data
  );
};

export const putModule = async (data: Partial<Module>): Promise<Module> => {
  return await apiExecutePut<Module>(
    `/${EndpointType.MODULE}/`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const registerGetUsedModuleNames = (
  taskType: string,
  callback: (result: string[], taskType: string) => void,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<string[]> => {
  return cashService.registerSimplifiedGet<string[]>(
    `/${EndpointType.MODULE_NAMES}/${taskType}/`,
    callback,
    [],
    EndpointAuthorisationType.MODERATOR,
    maxDelaySeconds,
    null,
    [taskType]
  );
};

export const deregisterGetUsedModuleNames = (
  taskType: string,
  callback: (result: string[], taskType: string) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.MODULE_NAMES}/${taskType}/`,
    callback
  );
};
