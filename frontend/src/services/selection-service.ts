import {
  apiExecuteDelete,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Selection } from '@/types/api/Selection';
import { Idea } from '@/types/api/Idea';
import { getIdeaImages } from '@/services/idea-service';
import * as cashService from '@/services/cash-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const registerGetSelectionById = (
  id: string,
  callback: (result: Selection) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Selection> => {
  return cashService.registerSimplifiedGet<Selection>(
    `/${EndpointType.SELECTION}/${id}/`,
    callback,
    {},
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetSelectionById = (
  id: string,
  callback: (result: Selection) => void
): void => {
  cashService.deregisterGet(`/${EndpointType.SELECTION}/${id}/`, callback);
};

export const deleteSelection = async (id: string): Promise<boolean> => {
  return await apiExecuteDelete<any>(`/${EndpointType.SELECTION}/${id}/`);
};

export const postSelection = async (
  topicId: string,
  data: Partial<Selection>
): Promise<Selection> => {
  return await apiExecutePost<Selection>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.SELECTION}`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const putSelection = async (
  data: Partial<Selection>
): Promise<Selection> => {
  return await apiExecutePut<Selection>(
    `/${EndpointType.SELECTION}`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const registerGetSelectionForTopic = (
  topicId: string,
  callback: (result: Selection[]) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Selection[]> => {
  return cashService.registerSimplifiedGet<Selection[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.SELECTIONS}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetSelectionForTopic = (
  topicId: string,
  callback: (result: Selection[]) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.SELECTIONS}`,
    callback
  );
};

export const addIdeasToSelection = async (
  selectionId: string,
  data: Partial<string[]>,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<void> => {
  await apiExecutePost<any>(
    `/${EndpointType.SELECTION}/${selectionId}/${EndpointType.IDEAS}`,
    data,
    authHeaderType
  );
};

export const removeIdeasFromSelection = async (
  selectionId: string,
  data: Partial<string[]>,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<boolean> => {
  return await apiExecuteDelete<any>(
    `/${EndpointType.SELECTION}/${selectionId}/${EndpointType.IDEAS}`,
    data,
    authHeaderType,
    false
  );
};

export const registerGetIdeasForSelection = (
  selectionId: string,
  callback: (result: Idea[]) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Idea[]> => {
  return cashService.registerSimplifiedGet<Idea[]>(
    `/${EndpointType.SELECTION}/${selectionId}/${EndpointType.IDEAS}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (ideas: Idea[]) => {
      return await getIdeaImages(ideas, authHeaderType);
    }
  );
};
export const deregisterGetIdeasForSelection = (
  selectionId: string,
  callback: (result: Idea[]) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.SELECTION}/${selectionId}/${EndpointType.IDEAS}`,
    callback
  );
};
