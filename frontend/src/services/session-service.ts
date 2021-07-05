import { Task } from '@/services/task-service';
import { apiEndpoint } from '@/services/api';
import UserType from '@/types/UserType';
import { Topic } from '@/services/topic-service';
import EndpointType from '@/types/Endpoint';
import ApiResponse from '@/types/ApiResponse';

// TODO: move types to separate files in types folder?
export interface Session {
  connectionKey: string;
  creationDate: string;
  expirationDate: string;
  id: string;
  maxParticipants: number;
  description: string;
  publicScreenModuleId: string;
  role: UserType;
  title: string;
}

const API_SESSION_ENDPOINT = apiEndpoint(EndpointType.SESSION);

export const getList = async (): Promise<Session[]> => {
  const API_SESSIONS_ENDPOINT = apiEndpoint(EndpointType.SESSIONS);
  const { data } = await API_SESSIONS_ENDPOINT.get<Session[]>('/');
  return data;
};

export const getById = async (id: string): Promise<Session> => {
  const { data } = await API_SESSION_ENDPOINT.get<Session>(`/${id}/`);
  return data;
};

export const getClientSession = async (): Promise<Session> => {
  const API_SESSIONS_ENDPOINT = apiEndpoint(EndpointType.SESSIONS);
  const { data } = await API_SESSIONS_ENDPOINT.get<Session[]>('/');
  return data[0];
};

export const getTopicsList = async (sessionId: string): Promise<Topic[]> => {
  const { data } = await API_SESSION_ENDPOINT.get<Topic[]>(
    `/${sessionId}/${EndpointType.TOPICS}/`
  );
  return data;
};

export const post = async (data: Partial<Session>): Promise<Session> => {
  const { data: responseData } = await API_SESSION_ENDPOINT.post<Session>(
    '/',
    data
  );
  return responseData;
};

export const postTopic = async (
  sessionId: string,
  data: Partial<Topic>
): Promise<Topic> => {
  const { data: responseData } = await API_SESSION_ENDPOINT.post<Topic>(
    `/${sessionId}/${EndpointType.TOPIC}/`,
    data
  );
  return responseData;
};

export const patch = async (data: Partial<Session>): Promise<void> => {
  await API_SESSION_ENDPOINT.patch<Session>('/', data);
};

export const remove = async (id: string): Promise<void> => {
  await API_SESSION_ENDPOINT.delete<Session>(`/${id}/`);
};

export const getPublicScreen = async (
  sessionId: string
): Promise<Task | null> => {
  try {
    const { data } = await API_SESSION_ENDPOINT.get<Task>(
      `/${sessionId}/${EndpointType.PUBLIC_SCREEN}/`
    );
    return data;
  } catch (error) {
    return null;
  }
};

export const displayOnPublicScreen = async (
  sessionId: string,
  taskId: string
): Promise<ApiResponse> => {
  const { data } = await API_SESSION_ENDPOINT.put<ApiResponse>(
    `/${sessionId}/${EndpointType.PUBLIC_SCREEN}/${taskId}/`
  );
  return data;
};
