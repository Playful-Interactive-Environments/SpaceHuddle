import { Task } from '@/services/task-service';
import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut
} from '@/services/api';
import UserType from '@/types/UserType';
import EndpointType from '@/types/EndpointType';
import EndpointAuthorisationType from "@/types/EndpointAuthorisationType";

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

export const getList = async (
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Session[]> => {
  return await apiExecuteGetHandled<Session[]>(
    `/${EndpointType.SESSIONS}/`,
    [],
    authHeaderType
  );
};

export const getById = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Session> => {
  return await apiExecuteGetHandled<Session>(
    `/${EndpointType.SESSION}/${id}/`,
    {},
    authHeaderType
  );
};

export const getClientSession = async (
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Session> => {
  const result = await apiExecuteGetHandled<Session[]>(
    `/${EndpointType.SESSIONS}/`,
    [],
    authHeaderType
  );
  if (Array.isArray(result) && result.length > 0) {
    return result[0];
  }
  return {} as Session;
};

export const post = async (data: Partial<Session>): Promise<Session> => {
  return await apiExecutePost<Session>(
    `/${EndpointType.SESSION}/`,
    data
  );
};

export const remove = async (id: string): Promise<void> => {
  return await apiExecuteDelete<any>(
    `/${EndpointType.SESSION}/${id}/`
  );
};

export const getPublicScreen = async (
  sessionId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Task | null> => {
  return await apiExecuteGetHandled<Task>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.PUBLIC_SCREEN}/`,
    null,
    authHeaderType
  );
};

export const displayOnPublicScreen = async (
  sessionId: string,
  taskId: string
): Promise<Session> => {
  return await apiExecutePut<Session>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.PUBLIC_SCREEN}/${taskId}/`,
    {}
  );
};
