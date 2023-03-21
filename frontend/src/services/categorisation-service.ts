import {
  apiExecuteDelete,
  apiExecutePost, apiExecutePostHandled,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Category } from '@/types/api/Category';
import { Idea } from '@/types/api/Idea';
import { getIdeaImages } from '@/services/idea-service';
import * as cashService from '@/services/cash-service';
import {Topic} from "@/types/api/Topic";

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const registerGetCategoryById = (
  id: string,
  callback: (result: Category) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Category> => {
  return cashService.registerSimplifiedGet<Category>(
    `/${EndpointType.CATEGORY}/${id}/`,
    callback,
    {},
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetCategoryById = (
  id: string,
  callback: (result: Category) => void
): void => {
  cashService.deregisterGet(`/${EndpointType.CATEGORY}/${id}/`, callback);
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
  data: Partial<Category>
): Promise<Category> => {
  return await apiExecutePut<Category>(
    `/${EndpointType.CATEGORY}`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const registerGetCategoriesForTask = (
  taskId: string,
  callback: (result: Category[]) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Category[]> => {
  return cashService.registerSimplifiedGet<Category[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.CATEGORIES}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetCategoriesForTask = (
  taskId: string,
  callback: (result: Category[]) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.CATEGORIES}`,
    callback
  );
};

export const registerGetCategoryIdeas = (
  id: string,
  callback: (result: Idea[]) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Idea[]> => {
  return cashService.registerSimplifiedGet<Idea[]>(
    `/${EndpointType.CATEGORY}/${id}/${EndpointType.IDEAS}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (ideas: Idea[]) => {
      return await getIdeaImages(ideas, authHeaderType);
    }
  );
};

export const deregisterGetCategoryIdeas = (
  id: string,
  callback: (result: Idea[]) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.CATEGORY}/${id}/${EndpointType.IDEAS}`,
    callback
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

export const clone = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Topic> => {
  return apiExecutePostHandled<Topic>(
    `/${EndpointType.CATEGORY}/${id}/clone`,
    null,
    null,
    authHeaderType
  );
};
