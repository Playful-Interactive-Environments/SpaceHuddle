import { apiExecuteGetHandled, apiExecutePost } from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Topic } from '@/types/api/Topic';

export const postTopic = async (
  sessionId: string,
  data: Partial<Topic>
): Promise<Topic> => {
  return await apiExecutePost<Topic>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.TOPIC}/`,
    data
  );
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
