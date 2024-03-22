import { Topic } from '@/types/api/Topic';
import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Participant } from '@/types/api/Participant';
import * as cashService from '@/services/cash-service';

export const connect = async (
  sessionKey: string
): Promise<Participant | Partial<Participant>> => {
  cashService.resetCash();
  return await apiExecutePost<Participant>(
    `/${EndpointType.PARTICIPANT_CONNECT}/`,
    {
      sessionKey,
    },
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const addParticipant = async (
  sessionKey: string,
  count = 1
): Promise<Participant | Partial<Participant>[]> => {
  const list: Promise<Participant | Partial<Participant>>[] = [];
  for (let i = 0; i < count; i++) {
    list.push(
      apiExecutePost<Participant>(
        `/${EndpointType.PARTICIPANT_CONNECT}/`,
        {
          sessionKey,
        },
        EndpointAuthorisationType.MODERATOR
      )
    );
  }
  return await Promise.all(list);
};

export const reconnect = async (browserKey: string): Promise<Participant> => {
  cashService.resetCash();
  return await apiExecuteGetHandled<Participant>(
    `/${EndpointType.PARTICIPANT_RECONNECT}/${browserKey}/`,
    null,
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const registerGetTopicList = (
  callback: (result: Topic[]) => void,
  authHeaderType = EndpointAuthorisationType.PARTICIPANT,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Topic[]> => {
  return cashService.registerSimplifiedGet<Topic[]>(
    `/${EndpointType.PARTICIPANT}/${EndpointType.TOPICS}/`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetTopicList = (
  callback: (result: Topic[]) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.PARTICIPANT}/${EndpointType.TOPICS}/`,
    callback
  );
};

export const remove = async (
  id: string,
  confirmCheck = true
): Promise<boolean> => {
  return await apiExecuteDelete<boolean>(
    `/${EndpointType.PARTICIPANT}/${id}/`,
    null,
    EndpointAuthorisationType.MODERATOR,
    confirmCheck
  );
};

export const changeParameter = async (parameter: object): Promise<boolean> => {
  return await apiExecutePut<boolean>(
    `/${EndpointType.PARTICIPANT}/${EndpointType.PARAMETER}/`,
    { parameter: parameter },
    EndpointAuthorisationType.PARTICIPANT
  );
};
