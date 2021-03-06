import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Selection } from '@/types/api/Selection';
import { Idea } from '@/types/api/Idea';
import { getIdeaImages } from '@/services/idea-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const getSelectionById = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Selection> => {
  return await apiExecuteGetHandled<Selection>(
    `/${EndpointType.SELECTION}/${id}/`,
    {},
    authHeaderType
  );
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

export const getSelectionForTopic = async (
  topicId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Selection[]> => {
  return await apiExecuteGetHandled<Selection[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.SELECTIONS}`,
    [],
    authHeaderType
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

export const getIdeasForSelection = async (
  selectionId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[]> => {
  const ideas = await apiExecuteGetHandled<Idea[]>(
    `/${EndpointType.SELECTION}/${selectionId}/${EndpointType.IDEAS}`,
    [],
    authHeaderType
  );
  return await getIdeaImages(ideas, authHeaderType);
};
