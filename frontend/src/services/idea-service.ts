import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
} from '@/services/api';
import EndpointType from '@/types/Endpoint';

export interface Idea {
  id: string;
  state: string; //what are the states of an idea??
  timestamp: string;
  description: string;
  keywords: string;
  image: string; //ignore at first?
  link: string; //link to where??
}

export const deleteIdea = async (id: string): Promise<void> => {
  return await apiExecuteDelete<any>(
    `/${EndpointType.IDEA}/${id}/`
  );
};

export const postIdea = async (
  taskId: string,
  data: Partial<Idea>
): Promise<Idea> => {
  return await apiExecutePost<Idea>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.IDEA}`,
    data
  );
};

export const getIdeasForTask = async (taskId: string): Promise<Idea[]> => {
  return await apiExecuteGetHandled<Idea[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.IDEAS}/`,
    []
  );
};
