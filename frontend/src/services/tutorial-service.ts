import { apiExecuteGetHandled, apiExecutePost } from '@/services/api';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const getList = async (
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Tutorial[]> => {
  return await apiExecuteGetHandled<Tutorial[]>(
    `/tutorial_steps/`,
    [],
    authHeaderType
  );
};

export const postStep = async (data: Tutorial): Promise<Tutorial> => {
  return await apiExecutePost<Tutorial>(`/tutorial_step/`, data);
};
