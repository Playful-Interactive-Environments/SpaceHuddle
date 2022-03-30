import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Hierarchy } from '@/types/api/Hierarchy';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const getById = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Hierarchy> => {
  return await apiExecuteGetHandled<Hierarchy>(
    `/${EndpointType.HIERARCHY}/${id}/`,
    {},
    authHeaderType
  );
};

export const deleteHierarchy = async (id: string): Promise<boolean> => {
  return await apiExecuteDelete<any>(`/${EndpointType.HIERARCHY}/${id}/`);
};

export const postHierarchy = async (
  taskId: string,
  data: Partial<Hierarchy>
): Promise<Hierarchy> => {
  return await apiExecutePost<Hierarchy>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.HIERARCHY}`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const putHierarchy = async (
  data: Partial<Hierarchy>
): Promise<Hierarchy> => {
  return await apiExecutePut<Hierarchy>(
    `/${EndpointType.HIERARCHY}`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const getList = async (
  taskId: string,
  parentHierarchyId: string | null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Hierarchy[]> => {
  return await apiExecuteGetHandled<Hierarchy[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.HIERARCHIES}/${parentHierarchyId}`,
    [],
    authHeaderType
  );
};
