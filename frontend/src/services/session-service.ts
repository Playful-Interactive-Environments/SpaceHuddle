import { Task } from '@/types/api/Task';
import {
  apiExecuteDelete,
  apiExecutePost,
  apiExecutePostHandled,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Session, SessionInfo } from '@/types/api/Session';
import { ParticipantInfo } from '@/types/api/Participant';
import * as cashService from '@/services/cash-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const registerGetList = (
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Session[]> => {
  return cashService.registerSimplifiedGet<Session[]>(
    `/${EndpointType.SESSIONS}/`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetList = (callback: (result: any) => void): void => {
  cashService.deregisterGet(`/${EndpointType.SESSIONS}/`, callback);
};

export const registerGetById = (
  id: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Session> => {
  return cashService.registerSimplifiedGet<Session>(
    `/${EndpointType.SESSION}/${id}/`,
    callback,
    {},
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetById = (
  id: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(`/${EndpointType.SESSION}/${id}/`, callback);
};

export const registerGetParticipantSession = (
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.CashEntry<Session[], Session> => {
  return cashService.registerGet<Session[], Session>(
    `/${EndpointType.SESSIONS}/`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (result: Session[]) => {
      if (Array.isArray(result) && result.length > 0) {
        return result[0];
      }
      return {} as Session;
    }
  );
};

export const deregisterGetParticipantSession = (
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(`/${EndpointType.SESSIONS}/`, callback);
};

export const post = async (data: Partial<Session>): Promise<Session> => {
  return await apiExecutePost<Session>(`/${EndpointType.SESSION}/`, data);
};

export const put = async (data: Partial<Session>): Promise<Session> => {
  return await apiExecutePut<Session>(
    `/${EndpointType.SESSION}/`,
    data,
    EndpointAuthorisationType.MODERATOR
  );
};

export const remove = async (id: string): Promise<boolean> => {
  return await apiExecuteDelete<any>(`/${EndpointType.SESSION}/${id}/`);
};

export const registerGetPublicScreen = (
  sessionId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Task | null> => {
  return cashService.registerSimplifiedGet<Task | null>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.PUBLIC_SCREEN}/`,
    callback,
    null,
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetPublicScreen = (
  sessionId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.PUBLIC_SCREEN}/`,
    callback
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

export const getSessionInfos = async (
  connection_keys: string[],
  authHeaderType = EndpointAuthorisationType.UNAUTHORISED
): Promise<SessionInfo[]> => {
  return await apiExecutePostHandled<SessionInfo[]>(
    `/${EndpointType.SESSION_INFOS}/`,
    connection_keys,
    null,
    authHeaderType
  );
};

export const registerGetParticipants = (
  sessionId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): void => {
  cashService.registerSimplifiedGet<ParticipantInfo[]>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.PARTICIPANTS}/`,
    callback,
    null,
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetParticipants = (
  sessionId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.PARTICIPANTS}/`,
    callback
  );
};

export const clone = async (
  sessionId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Session> => {
  return apiExecutePostHandled<Session>(
    `/${EndpointType.SESSION}/${sessionId}/clone`,
    null,
    null,
    authHeaderType
  );
};
