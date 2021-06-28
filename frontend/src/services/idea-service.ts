import { apiEndpoint } from '@/services/api';
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

const API_IDEA_ENDPOINT = apiEndpoint(EndpointType.IDEA);

export const deleteIdea = async (id: string): Promise<void> => {
  await API_IDEA_ENDPOINT.delete<any>(`/${id}/`);
};
