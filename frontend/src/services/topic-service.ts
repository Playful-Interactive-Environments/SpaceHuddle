import {apiExecuteGet, apiExecuteGetHandled, apiExecutePost} from '@/services/api';
import { Task } from '@/services/task-service';
import EndpointType from '@/types/EndpointType';
import EndpointAuthorisationType from "@/types/EndpointAuthorisationType";

export interface Topic {
  id: string;
  title: string;
  description: string;
  activeTaskId: string;
  tasks?: Task[];
}

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
