import { apiExecutePut } from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import {TaskParticipantState, TaskParticipantStateSum} from '@/types/api/TaskParticipantState';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const registerGetList = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<TaskParticipantState[]> => {
  return cashService.registerSimplifiedGet<TaskParticipantState[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_STATE}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetList = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_STATE}`,
    callback
  );
};

export const registerGetListFromTopic = (
  topicId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<TaskParticipantStateSum[]> => {
  return cashService.registerSimplifiedGet<TaskParticipantStateSum[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.PARTICIPANT_STATE}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetListFromTopic = (
  topicId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.PARTICIPANT_STATE}`,
    callback
  );
};

export const put = async (
  taskId: string,
  data: Partial<TaskParticipantState>
): Promise<TaskParticipantState> => {
  return await apiExecutePut<TaskParticipantState>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_STATE}`,
    data,
    EndpointAuthorisationType.PARTICIPANT
  );
};
