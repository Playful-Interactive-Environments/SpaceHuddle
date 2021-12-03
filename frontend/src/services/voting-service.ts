import { Vote, VoteResult } from '@/types/api/Vote';
import { apiExecuteGetHandled, apiExecutePost, apiExecutePut } from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

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

export const putVote = async (
  id: string,
  data: Partial<Vote>
): Promise<Vote> => {
  data['id'] = id;
  return await apiExecutePut<Vote>(
    `/${EndpointType.VOTE}/`,
    data,
    EndpointAuthorisationType.PARTICIPANT
  );
};

export const getVotes = async (
  taskId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Vote[]> => {
  return await apiExecuteGetHandled<Vote[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.VOTES}`,
    [],
    authHeaderType
  );
};

export const getResult = async (
  taskId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<VoteResult[]> => {
  return await apiExecuteGetHandled<VoteResult[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.VOTE_RESULT}`,
    [],
    authHeaderType
  );
};

export const getHierarchyVotes = async (
  parentIdeaId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Vote[]> => {
  return await apiExecuteGetHandled<Vote[]>(
    `/${EndpointType.HIERARCHY}/${parentIdeaId}/${EndpointType.VOTES}`,
    [],
    authHeaderType
  );
};

export const getHierarchyResult = async (
  parentIdeaId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<VoteResult[]> => {
  return await apiExecuteGetHandled<VoteResult[]>(
    `/${EndpointType.HIERARCHY}/${parentIdeaId}/${EndpointType.VOTE_RESULT}`,
    [],
    authHeaderType
  );
};
