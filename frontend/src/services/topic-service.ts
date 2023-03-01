import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePostHandled,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Topic } from '@/types/api/Topic';
import { TopicExport } from '@/types/api/TopicExport';
import * as cashService from '@/services/cash-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const registerGetTopicById = (
  id: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Topic> => {
  return cashService.registerSimplifiedGet<Topic>(
    `/${EndpointType.TOPIC}/${id}/`,
    callback,
    {},
    authHeaderType,
    maxDelaySeconds
  );
};
export const deregisterGetTopicById = (
  id: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(`/${EndpointType.TOPIC}/${id}/`, callback);
};

export const postTopic = async (
  sessionId: string,
  data: Partial<Topic>
): Promise<Topic> => {
  return await apiExecutePost<Topic>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.TOPIC}/`,
    data
  );
};

export const putTopic = async (data: Partial<Topic>): Promise<Topic> => {
  return await apiExecutePut<Topic>(
    `/${EndpointType.TOPIC}/`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const deleteTopic = async (id: string): Promise<boolean> => {
  return await apiExecuteDelete<any>(`/${EndpointType.TOPIC}/${id}/`);
};

export const registerGetTopicsList = (
  sessionId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Topic[]> => {
  return cashService.registerSimplifiedGet<Topic[]>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.TOPICS}/`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetTopicsList = (
  sessionId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.TOPICS}/`,
    callback
  );
};

export const exportTopic = async (
  topicId: string,
  exportType: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<TopicExport> => {
  return await apiExecuteGetHandled<TopicExport>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.EXPORT}/${exportType}/`,
    {},
    authHeaderType
  );
};

export const clone = async (
  topicId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Topic> => {
  return apiExecutePostHandled<Topic>(
    `/${EndpointType.TOPIC}/${topicId}/clone`,
    null,
    null,
    authHeaderType
  );
};
