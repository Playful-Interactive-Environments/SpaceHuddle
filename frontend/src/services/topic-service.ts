import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Topic } from '@/types/api/Topic';
import { TopicExport } from '@/types/api/TopicExport';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const getTopicById = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Topic> => {
  return await apiExecuteGetHandled<Topic>(
    `/${EndpointType.TOPIC}/${id}/`,
    {},
    authHeaderType
  );
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

export const getTopicsList = async (
  sessionId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Topic[]> => {
  return await apiExecuteGetHandled<Topic[]>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.TOPICS}/`,
    [],
    authHeaderType
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
