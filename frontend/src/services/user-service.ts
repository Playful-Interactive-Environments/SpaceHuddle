import {
  apiExecuteDelete,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import ApiResponse from '@/types/api/ApiResponse';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import { UserState } from '@/types/api/UserState';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const registerUser = async (
  email: string,
  password: string,
  passwordRepeat: string
): Promise<any> => {
  return await apiExecutePost<any>(
    `/${EndpointType.USER}/${EndpointType.REGISTER}/`,
    {
      username: email,
      password: password,
      passwordConfirmation: passwordRepeat,
    },
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const changePassword = async (
  oldPassword: string,
  newPassword: string,
  passwordRepeat: string
): Promise<any> => {
  return await apiExecutePut<any>(
    `/${EndpointType.USER}/`,
    {
      oldPassword: oldPassword,
      password: newPassword,
      passwordConfirmation: passwordRepeat,
    },
    EndpointAuthorisationType.MODERATOR
  );
};

export const changeParameter = async (parameter: object): Promise<boolean> => {
  return await apiExecutePut<boolean>(
    `/${EndpointType.USER}/${EndpointType.PARAMETER}/`,
    { parameter: parameter },
    EndpointAuthorisationType.MODERATOR
  );
};

export const loginUser = async (
  email: string,
  password: string
): Promise<ApiResponse> => {
  cashService.resetCash();
  return apiExecutePost<ApiResponse>(
    `/${EndpointType.USER}/${EndpointType.LOGIN}/`,
    {
      username: email,
      password: password,
    },
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const deleteUser = async (): Promise<boolean> => {
  return await apiExecuteDelete<any>(`/${EndpointType.USER}/`);
};

export const resetPassword = async (username: string): Promise<ApiResponse> => {
  return await apiExecutePut<ApiResponse>(
    `/${EndpointType.USER}/reset/${username}/`,
    null,
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const changeForgetPassword = async (
  token: string,
  newPassword: string,
  passwordRepeat: string
): Promise<ApiResponse> => {
  return await apiExecutePut<ApiResponse>(
    `/${EndpointType.USER}/forget-password/`,
    {
      token: token,
      password: newPassword,
      passwordConfirmation: passwordRepeat,
    },
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const confirmEmail = async (token: string): Promise<ApiResponse> => {
  return await apiExecutePut<ApiResponse>(
    `/${EndpointType.USER}/confirm/${token}/`,
    null,
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const sendConfirmMail = async (email: string): Promise<ApiResponse> => {
  return await apiExecutePut<ApiResponse>(
    `/${EndpointType.USER}/send-confirm/${email}/`,
    null,
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const registerGetUserList = (
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<UserState[]> => {
  return cashService.registerSimplifiedGet<UserState[]>(
    `/users`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const confirmOtherUser = async (
  userId: string
): Promise<ApiResponse> => {
  return await apiExecutePut<ApiResponse>(
    `/${EndpointType.USER}/${userId}/confirm/`,
    null,
    EndpointAuthorisationType.UNAUTHORISED
  );
};
