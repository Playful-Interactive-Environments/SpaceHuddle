import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Category } from '@/types/api/Category';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const getCategoryById = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Category> => {
  return await apiExecuteGetHandled<Category>(
    `/${EndpointType.CATEGORY}/${id}/`,
    {},
    authHeaderType
  );
};

export const deleteCategory = async (id: string): Promise<boolean> => {
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

export const putCategory = async (
  id: string,
  data: Partial<Category>
): Promise<Category> => {
  data['id'] = id;
  return await apiExecutePut<Category>(
    `/${EndpointType.CATEGORY}`,
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

export const addIdeasToCategory = async (
  categoryId: string,
  data: Partial<string[]>,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<void> => {
  await apiExecutePost<any>(
    `/${EndpointType.CATEGORY}/${categoryId}/${EndpointType.IDEAS}`,
    data,
    authHeaderType
  );
};

export const removeIdeasFromCategory = async (
  categoryId: string,
  data: Partial<string[]>,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<boolean> => {
  return await apiExecuteDelete<any>(
    `/${EndpointType.CATEGORY}/${categoryId}/${EndpointType.IDEAS}`,
    data,
    authHeaderType,
    false
  );
};
