import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea } from '@/types/api/Idea';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const deleteIdea = async (id: string): Promise<boolean> => {
  return await apiExecuteDelete<any>(`/${EndpointType.IDEA}/${id}/`);
};

export const postIdea = async (
  taskId: string,
  data: Partial<Idea>
): Promise<Idea> => {
  return await apiExecutePost<Idea>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.IDEA}`,
    data,
    EndpointAuthorisationType.PARTICIPANT
  );
};

export const putIdea = async (
  id: string,
  data: Partial<Idea>
): Promise<Idea> => {
  data['id'] = id;
  return await apiExecutePut<Idea>(
    `/${EndpointType.IDEA}`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const getIdeasForTask = async (
  taskId: string,
  orderType: string | null = null,
  refId: string | null = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[]> => {
  let queryParameter = '';
  if (orderType) queryParameter = `?order=${orderType}`;
  if (refId && orderType) queryParameter = `${queryParameter}&refId=${refId}`;
  return await apiExecuteGetHandled<Idea[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.IDEAS}/${queryParameter}`,
    [],
    authHeaderType
  );
};

export const getIdeasForTopic = async (
  topicId: string,
  orderType: string | null = null,
  refId: string | null = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[]> => {
  let queryParameter = '';
  if (orderType) queryParameter = `?order=${orderType}`;
  if (refId && orderType) queryParameter = `${queryParameter}&refId=${refId}`;
  return await apiExecuteGetHandled<Idea[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.IDEAS}/${queryParameter}`,
    [],
    authHeaderType
  );
};
