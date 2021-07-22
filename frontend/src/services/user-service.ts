import { apiExecutePost } from '@/services/api';
import ApiResponse from '@/types/ApiResponse';
import EndpointType from '@/types/EndpointType';
import EndpointAuthorisationType from "@/types/EndpointAuthorisationType";

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
