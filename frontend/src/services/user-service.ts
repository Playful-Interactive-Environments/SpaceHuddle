import { apiEndpoint } from '@/services/api';
import { AxiosError } from 'axios';
import ApiResponse from '@/types/ApiResponse';
import EndpointType from '@/types/Endpoint';

const API_USER_ENDPOINT = apiEndpoint(EndpointType.USER);

export const registerUser = async (
  email: string,
  password: string,
  passwordRepeat: string
): Promise<ApiResponse> => {
  const { data } = await API_USER_ENDPOINT.post<ApiResponse>(
    `/${EndpointType.REGISTER}`,
    {
      username: email,
      password: password,
      passwordConfirmation: passwordRepeat,
    }
  );
  return data;
};

export const loginUser = async (
  email: string,
  password: string
): Promise<ApiResponse> => {
  try {
    const { data } = await API_USER_ENDPOINT.post<ApiResponse>(
      `/${EndpointType.LOGIN}`,
      {
        username: email,
        password: password,
      }
    );
    return data;
  } catch (error) {
    return (error as AxiosError).response?.data;
  }
};
