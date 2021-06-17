import { apiEndpoint } from '@/services/api';
import { Task } from '@/services/moderator/task-service';

export interface Topic {
  id: string;
  title: string;
  description: string;
  activeTaskId: string;
}

const API_TOPIC_ENDPOINT = apiEndpoint('topic/');

export const getTaskList = async (topicId: string): Promise<Task[]> => {
  const { data } = await API_TOPIC_ENDPOINT.get<Task[]>(`/${topicId}/tasks`);
  return data;
};
