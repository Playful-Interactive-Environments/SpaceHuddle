import { apiExecutePost, apiExecutePut } from '@/services/api';
import ApiResponse from '@/types/api/ApiResponse';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

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

export const loginUser = async (
  email: string,
  password: string
): Promise<ApiResponse> => {
  return await apiExecutePost<ApiResponse>(
    `/${EndpointType.USER}/${EndpointType.LOGIN}/`,
    {
      username: email,
      password: password,
    },
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const resetPassword = async (username: string): Promise<any> => {
  return await apiExecutePut<any>(
    `/${EndpointType.USER}/reset/${username}/`,
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const changeForgetPassword = async (
  token: string,
  newPassword: string,
  passwordRepeat: string
): Promise<any> => {
  return await apiExecutePut<any>(
    `/${EndpointType.USER}/forget-password/`,
    {
      token: token,
      password: newPassword,
      passwordConfirmation: passwordRepeat,
    },
    EndpointAuthorisationType.MODERATOR
  );
};
