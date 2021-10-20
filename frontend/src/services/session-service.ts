import { Task } from '@/types/api/Task';
import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Session } from '@/types/api/Session';

/* eslint-disable @typescript-eslint/no-explicit-any*/

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

export const getParticipantSession = async (
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
  return await apiExecutePost<Session>(`/${EndpointType.SESSION}/`, data);
};

export const put = async (
  id: string,
  data: Partial<Session>
): Promise<Session> => {
  data['id'] = id;
  return await apiExecutePut<Session>(
    `/${EndpointType.SESSION}/`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const remove = async (id: string): Promise<boolean> => {
  return await apiExecuteDelete<any>(`/${EndpointType.SESSION}/${id}/`);
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
