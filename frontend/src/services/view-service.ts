import { apiExecuteGetHandled } from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea } from '@/types/api/Idea';
import { View } from '@/types/api/View';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const getDetail = async (
  type: string,
  typeId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[]> => {
  return await apiExecuteGetHandled<Idea[]>(
    `/${EndpointType.VIEW}/${type}/${typeId}/`,
    [],
    authHeaderType
  );
};

export const getList = async (
  topicId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<View[]> => {
  return await apiExecuteGetHandled<View[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.VIEWS}`,
    [],
    authHeaderType
  );
};
