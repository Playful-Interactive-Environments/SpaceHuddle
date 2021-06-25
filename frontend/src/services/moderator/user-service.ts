import { apiEndpoint } from '@/services/api';
import ApiResponse from '@/types/ApiResponse';
import EndpointType from '@/types/Endpoint';

const API_USER_ENDPOINT = apiEndpoint(EndpointType.USER);

export const registerUser = async (
  email: string,
  password: string,
  passwordRepeat: string
): Promise<ApiResponse> => {
  const data = await API_USER_ENDPOINT.post<ApiResponse>(`/register/`, {
    email: email,
    password: password,
    passwordConfirmation: passwordRepeat,
  });
  console.log(data);
  return data.data;
};

export const loginUser = async (
  email: string,
  password: string
): Promise<ApiResponse> => {
  const data = await API_USER_ENDPOINT.post<ApiResponse>(`/login/`, {
    email: email,
    password: password,
  });
  console.log(data);
  return data.data;
};
