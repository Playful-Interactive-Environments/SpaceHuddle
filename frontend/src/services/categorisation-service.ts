import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Category } from '@/types/api/Category';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const deleteCategory = async (id: string): Promise<void> => {
  return await apiExecuteDelete<any>(`/${EndpointType.CATEGORY}/${id}/`);
};

export const postCategory = async (
  taskId: string,
  data: Partial<Category>
): Promise<Category> => {
  return await apiExecutePost<Category>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.CATEGORY}`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const getCategoriesForTask = async (
  taskId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Category[]> => {
  return await apiExecuteGetHandled<Category[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.CATEGORIES}`,
    [],
    authHeaderType
  );
};
