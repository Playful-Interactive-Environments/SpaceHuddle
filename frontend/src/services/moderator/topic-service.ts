import { apiEndpoint } from '@/services/api';
import { Task } from '@/services/moderator/task-service';
import EndpointType from '@/types/Endpoint';

export interface Topic {
  id: string;
  title: string;
  description: string;
  activeTaskId: string;
}

const API_TOPIC_ENDPOINT = apiEndpoint(EndpointType.TOPIC);

export const getTaskList = async (topicId: string): Promise<Task[]> => {
  const { data } = await API_TOPIC_ENDPOINT.get<Task[]>(
    `/${topicId}/${EndpointType.TASKS}`
  );
  return data;
};
