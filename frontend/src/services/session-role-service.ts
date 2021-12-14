import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { SessionRole } from '@/types/api/SessionRole';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const getList = async (
  sessionId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<SessionRole[]> => {
  return await apiExecuteGetHandled<SessionRole[]>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.AUTHORIZED_USERS}`,
    [],
    authHeaderType
  );
};

export const getOwn = async (
  sessionId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<SessionRole> => {
  return await apiExecuteGetHandled<SessionRole>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.OWN_USER_ROLE}`,
    {},
    authHeaderType
  );
};

export const post = async (
  sessionId: string,
  data: Partial<SessionRole>
): Promise<SessionRole> => {
  return await apiExecutePost<SessionRole>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.AUTHORIZED_USER}`,
    data
  );
};

export const put = async (
  sessionId: string,
  data: Partial<SessionRole>
): Promise<SessionRole> => {
  return await apiExecutePut<SessionRole>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.AUTHORIZED_USER}`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const remove = async (
  sessionId: string,
  username: string
): Promise<boolean> => {
  return await apiExecuteDelete<any>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.AUTHORIZED_USER}/${username}`
  );
};

export const removeOwn = async (sessionId: string): Promise<boolean> => {
  return await apiExecuteDelete<any>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.OWN_USER_ROLE}`
  );
};
