import { Vote, VoteResult } from '@/types/api/Vote';
import {
  apiExecuteDelete,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const postVote = async (
  taskId: string,
  data: Partial<Vote>
): Promise<Vote> => {
  return await apiExecutePost<Vote>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.VOTE}`,
    data,
    EndpointAuthorisationType.PARTICIPANT
  );
};

export const putVote = async (data: Partial<Vote>): Promise<Vote> => {
  return await apiExecutePut<Vote>(
    `/${EndpointType.VOTE}/`,
    data,
    EndpointAuthorisationType.PARTICIPANT
  );
};

export const deleteVote = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.PARTICIPANT,
  confirmCheck = false
): Promise<boolean> => {
  return await apiExecuteDelete<any>(
    `/${EndpointType.VOTE}/${id}/`,
    null,
    authHeaderType,
    confirmCheck
  );
};

export const registerGetVotes = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Vote[]> => {
  return cashService.registerSimplifiedGet<Vote[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.VOTES}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetVotes = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.VOTES}`,
    callback
  );
};

export const registerGetResult = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<VoteResult[]> => {
  return cashService.registerSimplifiedGet<VoteResult[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.VOTE_RESULT}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetResult = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.VOTE_RESULT}`,
    callback
  );
};

export const registerGetParentResult = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<VoteResult[]> => {
  return cashService.registerSimplifiedGet<VoteResult[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.VOTE_RESULT_PARENT}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetParentResult = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.VOTE_RESULT_PARENT}`,
    callback
  );
};

export const registerGetHierarchyVotes = (
  parentIdeaId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Vote[]> => {
  return cashService.registerSimplifiedGet<Vote[]>(
    `/${EndpointType.HIERARCHY}/${parentIdeaId}/${EndpointType.VOTES}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetHierarchyVotes = (
  parentIdeaId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.HIERARCHY}/${parentIdeaId}/${EndpointType.VOTES}`,
    callback
  );
};

export const registerGetHierarchyResult = (
  parentIdeaId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<VoteResult[]> => {
  return cashService.registerSimplifiedGet<VoteResult[]>(
    `/${EndpointType.HIERARCHY}/${parentIdeaId}/${EndpointType.VOTE_RESULT}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetHierarchyResult = (
  parentIdeaId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.HIERARCHY}/${parentIdeaId}/${EndpointType.VOTE_RESULT}`,
    callback
  );
};
