import {
  apiExecuteDelete,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { SessionRole } from '@/types/api/SessionRole';
import * as cashService from '@/services/cash-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const registerGetList = (
  sessionId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<SessionRole[]> => {
  return cashService.registerSimplifiedGet<SessionRole[]>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.AUTHORIZED_USERS}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetList = (
  sessionId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.AUTHORIZED_USERS}`,
    callback
  );
};

export const registerGetOwn = (
  sessionId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<SessionRole> => {
  return cashService.registerSimplifiedGet<SessionRole>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.OWN_USER_ROLE}`,
    callback,
    {},
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetOwn = (
  sessionId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.OWN_USER_ROLE}`,
    callback
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
