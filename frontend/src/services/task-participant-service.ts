import { apiExecutePost, apiExecutePut } from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import {
  TaskParticipantState,
  TaskParticipantStateSum,
} from '@/types/api/TaskParticipantState';
import { TaskParticipantIteration } from '@/types/api/TaskParticipantIteration';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { convertAvatarToString } from '@/types/api/Participant';

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

export const registerGetListFromSession = (
  sessionId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<TaskParticipantState[]> => {
  return cashService.registerSimplifiedGet<TaskParticipantState[]>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.PARTICIPANT_STATE}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const registerGetPoints = (
  sessionId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5,
  startingPoints: number
): cashService.SimplifiedCashEntry<TaskParticipantState[]> => {
  return cashService.registerSimplifiedGet<TaskParticipantState[]>(
    `/${EndpointType.SESSION}/${sessionId}/${EndpointType.PARTICIPANT_STATE}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (items: TaskParticipantState[]) =>
      calculatePoints(items, startingPoints),
    null,
    'points::'
  );
};

export const refreshPoints = (sessionId: string): void => {
  cashService.refreshCash(
    `points::/${EndpointType.SESSION}/${sessionId}/${EndpointType.PARTICIPANT_STATE}`
  );
};

export const calculatePoints = (
  items: TaskParticipantState[],
  startingPoints: number
): number => {
  let points = 0;
  for (const item of items) {
    if (item.parameter.gameplayResult) {
      const gameplayResult = item.parameter.gameplayResult;
      points += gameplayResult.points;
      if (gameplayResult.pointsSpent) points -= gameplayResult.pointsSpent;
    }
  }
  return points + startingPoints;
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

export const putParticipantState = async (
  taskId: string,
  data: Partial<TaskParticipantState>
): Promise<TaskParticipantState> => {
  return await apiExecutePut<TaskParticipantState>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_STATE}`,
    data,
    EndpointAuthorisationType.PARTICIPANT
  );
};

export const registerGetIterationList = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<TaskParticipantIteration[]> => {
  return cashService.registerSimplifiedGet<TaskParticipantIteration[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetIterationList = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}`,
    callback
  );
};

export const registerGetLastIteration = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<TaskParticipantIteration> => {
  return cashService.registerSimplifiedGet<TaskParticipantIteration>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}/${EndpointType.LAST}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetLastIteration = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}/${EndpointType.LAST}`,
    callback
  );
};

export const postParticipantIteration = async (
  taskId: string,
  data: Partial<TaskParticipantIteration>
): Promise<TaskParticipantIteration> => {
  return await apiExecutePost<TaskParticipantIteration>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}`,
    data,
    EndpointAuthorisationType.PARTICIPANT
  );
};

export const putParticipantIteration = async (
  taskId: string,
  data: Partial<TaskParticipantIteration>
): Promise<TaskParticipantIteration> => {
  return await apiExecutePut<TaskParticipantIteration>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}`,
    data,
    EndpointAuthorisationType.PARTICIPANT
  );
};

export const registerGetIterationStepList = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<TaskParticipantIterationStep[]> => {
  return cashService.registerSimplifiedGet<TaskParticipantIterationStep[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}/${EndpointType.STEP}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const addReplayCountToSteps = (
  data: TaskParticipantIterationStep[],
  parameterKey: (parameter: any) => boolean
): number => {
  const participantLevelCount: { [key: string]: number } = {};
  let maxReplays = 0;
  for (const item of data) {
    const avatarLevel = `${convertAvatarToString(item.avatar)}-${
      item.ideaId
    }-${parameterKey(item.parameter)}`;
    if (!(avatarLevel in participantLevelCount))
      participantLevelCount[avatarLevel] = 0;
    else participantLevelCount[avatarLevel]++;
    item.parameter.replayCount = participantLevelCount[avatarLevel];
    if (maxReplays < item.parameter.replayCount)
      maxReplays = item.parameter.replayCount;
  }
  return maxReplays;
};

export const deregisterGetIterationStepList = (
  taskId: string,
  callback: null | ((result: any) => void) = null
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}/${EndpointType.STEP}`,
    callback
  );
};

export const registerGetIterationStepFinalList = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<TaskParticipantIterationStep[]> => {
  return cashService.registerSimplifiedGet<TaskParticipantIterationStep[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}/${EndpointType.STEP}/${EndpointType.FINAL}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetIterationStepFinalList = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}/${EndpointType.STEP}/${EndpointType.FINAL}`,
    callback
  );
};

export const registerGetLastIterationStep = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<TaskParticipantIterationStep> => {
  return cashService.registerSimplifiedGet<TaskParticipantIterationStep>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}/${EndpointType.STEP}/${EndpointType.LAST}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetLastIterationStep = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}/${EndpointType.STEP}/${EndpointType.LAST}`,
    callback
  );
};

export const postParticipantIterationStep = async (
  taskId: string,
  data: Partial<TaskParticipantIterationStep>
): Promise<TaskParticipantIterationStep> => {
  return await apiExecutePost<TaskParticipantIterationStep>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}/${EndpointType.STEP}`,
    data,
    EndpointAuthorisationType.PARTICIPANT
  );
};

export const putParticipantIterationStep = async (
  taskId: string,
  data: Partial<TaskParticipantIterationStep>
): Promise<TaskParticipantIterationStep> => {
  return await apiExecutePut<TaskParticipantIterationStep>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.PARTICIPANT_ITERATION}/${EndpointType.STEP}`,
    data,
    EndpointAuthorisationType.PARTICIPANT
  );
};
