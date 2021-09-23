import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Module } from '@/types/api/Module';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const getModuleById = async (
  moduleId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Module> => {
  return await apiExecuteGetHandled<Module>(
    `/${EndpointType.MODULE}/${moduleId}/`,
    {},
    authHeaderType
  );
};

export const deleteModule = async (id: string): Promise<boolean> => {
  return await apiExecuteDelete<any>(`/${EndpointType.MODULE}/${id}/`);
};

export const getModuleList = async (
  taskId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Module[]> => {
  return await apiExecuteGetHandled<Module[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.MODULES}/`,
    [],
    authHeaderType
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

export const putModule = async (
  id: string,
  data: Partial<Module>
): Promise<Module> => {
  data['id'] = id;
  return await apiExecutePut<Module>(
    `/${EndpointType.MODULE}/`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};
