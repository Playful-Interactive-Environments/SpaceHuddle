import { apiExecutePut } from '@/services/api';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { TimerEntity } from '@/types/enum/TimerEntity';
import { convertToSaveVersion } from '@/types/api/Task';
import TaskStates from '@/types/enum/TaskStates';
import * as cashService from '@/services/cash-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export const update = async (
  entity: string,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  data: any,
  updateOnlyState = false
): Promise<any> => {
  if (Object.values(TimerEntity).find((item) => item == entity)) {
    if (entity == TimerEntity.TASK) {
      data = convertToSaveVersion(data);
      if (updateOnlyState) {
        data = {
          id: data.id,
          state: data.state,
        };
      }
    }
    const result = await apiExecutePut<any>(
      `/${entity}`,
      data,
      EndpointAuthorisationType.MODERATOR
    );
    return result;
  }
  return null;
};

export const registerGet = (
  entity: string,
  id: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<any> => {
  return cashService.registerSimplifiedGet<any>(
    `/${entity}/${id}/`,
    callback,
    {},
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGet = (
  entity: string,
  id: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(`/${entity}/${id}/`, callback);
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const getState = (data: any): string => {
  const parameter = 'parameter' in data ? data.parameter : {};
  if ('state' in data) return data.state;
  else if ('state' in parameter) return parameter.state;
  return TaskStates.WAIT;
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const setState = (data: any, value: string): any => {
  if ('state' in data) data.state = value;
  else if ('parameter' in data) data.parameter.state = value;
  return data;
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const getRemainingTime = (data: any): number | null => {
  const parameter = 'parameter' in data ? data.parameter : {};
  if ('remainingTime' in data) return data.remainingTime;
  else if ('remainingTime' in parameter) return parameter.remainingTime;
  return null;
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const setRemainingTime = (data: any, value: number | null): any => {
  if ('remainingTime' in data) data.remainingTime = value;
  else if ('parameter' in data) data.parameter.remainingTime = value;
  return data;
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const getTotalTime = (data: any): number | null => {
  const parameter = 'parameter' in data ? data.parameter : {};
  if ('totalTime' in parameter) return parameter.totalTime;
  return null;
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const setTotalTime = (data: any, value: number | null): any => {
  const parameter = 'parameter' in data ? data.parameter : {};
  if ('totalTime' in parameter) parameter.totalTime = value;
  return data;
};

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const isActive = (data: any): boolean => {
  if (data) {
    const state: string = getState(data);
    const remainingTime: number | null = getRemainingTime(data);
    return (
      state === TaskStates.ACTIVE &&
      (remainingTime === null || remainingTime > 0)
    );
  }
  return false;
};

export const getDate = (seconds: number): Date => {
  const date = new Date(seconds * 1000);
  date.setHours(date.getHours() + date.getTimezoneOffset() / 60);
  return date;
};

export const getSeconds = (date: Date | null): number | null => {
  if (!date) return null;
  return date.getHours() * 3600 + date.getMinutes() * 60 + date.getSeconds();
};
