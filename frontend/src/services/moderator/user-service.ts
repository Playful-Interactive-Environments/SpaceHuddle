import { apiEndpoint } from '@/services/api';
import { ApiResponse, LoginResponse } from '@/types/ApiResponse';
import EndpointType from '@/types/Endpoint';

const API_USER_ENDPOINT = apiEndpoint(EndpointType.USER);

export const registerUser = async (
  email: string,
  password: string,
  passwordRepeat: string
): Promise<ApiResponse> => {
  const data = await API_USER_ENDPOINT.post<ApiResponse>(`/register`, {
    username: email,
    password: password,
    passwordConfirmation: passwordRepeat,
  });
  return data.data;
};

export const loginUser = async (
  email: string,
  password: string
): Promise<LoginResponse> => {
  const data = await API_USER_ENDPOINT.post<LoginResponse>(`/login`, {
    username: email,
    password: password,
  });
  return data.data;
};
