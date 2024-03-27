import { Task } from '@/types/api/Task';
import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePostHandled,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Session, SessionInfo } from '@/types/api/Session';
import { ParticipantInfo } from '@/types/api/Participant';
import * as cashService from '@/services/cash-service';
import {
  SessionOrderGroup,
  SessionOrderGroupList,
  SessionSortOrderOption,
} from '@/types/api/SessionOrderGroup';
import SessionSortOrder from '@/types/enum/SessionSortOrder';
import { TopicExport } from '@/types/api/TopicExport';

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

export const refreshGetSessionList = (): void => {
  cashService.refreshCash(`/${EndpointType.SESSIONS}/`);
};

export const registerGetSubjects = (
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<string[]> => {
  return cashService.registerSimplifiedGet<string[]>(
    `/${EndpointType.SESSIONS}/subjects/`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetSubjects = (
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(`/${EndpointType.SESSIONS}/subjects/`, callback);
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

export const refreshGetPublicScreen = (sessionId: string): void => {
  cashService.refreshCash(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.PUBLIC_SCREEN}/`
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
): cashService.SimplifiedCashEntry<ParticipantInfo[]> => {
  return cashService.registerSimplifiedGet<ParticipantInfo[]>(
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

export const exportSession = async (
  sessionId: string,
  exportType: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<TopicExport> => {
  return await apiExecuteGetHandled<TopicExport>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.EXPORT}/${exportType}/`,
    {},
    authHeaderType
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

export const getQueryParameter = (orderType: string | null = null): string => {
  let queryParameter = getSessionListParameter(orderType);
  if (queryParameter.length > 0) queryParameter = `?${queryParameter}`;
  return queryParameter;
};

export const getSessionListParameter = (
  orderType: string | null = null
): string => {
  let queryParameter = '';
  if (orderType) queryParameter = `order=${orderType}`;
  return queryParameter;
};

export const getSessionSortOrderOptions = (): SessionSortOrderOption[] => {
  return Object.keys(SessionSortOrder).map((orderType) => {
    return { orderType: orderType.toLowerCase() };
  });
};

export const convertToOrderGroups = (
  sessions: Session[],
  orderAsc: boolean
): SessionOrderGroupList => {
  const orderGroupList = {};
  for (const orderType in SessionSortOrder) {
    let reorderedSessions: Session[] = [];
    sessions.forEach((session) => reorderedSessions.push(session));
    reorderedSessions.sort((session1, session2) => {
      switch (orderType) {
        case 'CHRONOLOGICAL':
          if (
            Date.parse(session1.creationDate) <
            Date.parse(session2.creationDate)
          ) {
            return -1;
          } else if (
            Date.parse(session1.creationDate) >
            Date.parse(session2.creationDate)
          ) {
            return 1;
          } else {
            return 0;
          }
        case 'ALPHABETICAL':
          return session1.title
            .toLowerCase()
            .localeCompare(session2.title.toLowerCase());
        case 'TOPICS':
          if (session1.topicCount < session2.topicCount) {
            return -1;
          } else if (session1.topicCount > session2.topicCount) {
            return 1;
          } else {
            return 0;
          }
        case 'TASKS':
          if (session1.taskCount < session2.taskCount) {
            return -1;
          } else if (session1.taskCount > session2.taskCount) {
            return 1;
          } else {
            return 0;
          }
        case 'MODERATORS':
          if (session1.userCount < session2.userCount) {
            return -1;
          } else if (session1.userCount > session2.userCount) {
            return 1;
          } else {
            return 0;
          }
        case 'PARTICIPANTS':
          if (session1.participantCount < session2.participantCount) {
            return -1;
          } else if (session1.participantCount > session2.participantCount) {
            return 1;
          } else {
            return 0;
          }
        default:
          console.warn('Sort-Order not recognized: ' + orderType);
          return 0;
      }
    });
    reorderedSessions = orderAsc
      ? reorderedSessions
      : reorderedSessions.reverse();
    orderGroupList[orderType] = new SessionOrderGroup(reorderedSessions);
  }
  return orderGroupList;
};

export const getOrderGroups = (
  sessions: Session[],
  orderAsc: boolean
): SessionOrderGroupList => {
  return convertToOrderGroups(sessions, orderAsc);
};

export const filterSessions = (
  sessionList: Session[],
  textFilter: string,
  subjects: string[] | null
): Session[] => {
  if (subjects !== null) {
    const tempList: Session[] = [];
    sessionList.forEach((session) =>
      subjects.forEach((subject) => {
        if (session.subject == subject) {
          tempList.push(session);
        }
      })
    );
    sessionList = tempList;
  }
  if (textFilter && textFilter.length > 0) {
    sessionList = sessionList.filter(
      (session) =>
        session.title.toLowerCase().includes(textFilter.toLowerCase()) ||
        session.description.toLowerCase().includes(textFilter.toLowerCase())
    );
  }
  return sessionList;
};
