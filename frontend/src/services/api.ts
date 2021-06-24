import Axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import { getJwt } from '@/services/moderator/auth-service';
import EndpointType from '@/types/Endpoint';

const endpointsWithAuthorization = [
  EndpointType.SESSIONS,
  EndpointType.SESSION,
  EndpointType.TOPICS,
  EndpointType.TOPIC,
  EndpointType.TASKS,
  EndpointType.TASK,
];

const endpointRequiresAuthorization = (endpoint: EndpointType) => {
  return endpointsWithAuthorization.includes(endpoint);
};

const interceptorAuthHeader = (
  axiosConfig: AxiosRequestConfig,
  endpoint: EndpointType
): AxiosRequestConfig => {
  if (endpointRequiresAuthorization(endpoint)) {
    const jwt = getJwt();
    if (!jwt) throw new Error('Missing Authentication Token');

    axiosConfig.headers = {
      Authorization: jwt,
    };
  }
  return axiosConfig;
};

export const apiEndpoint = (
  endpoint: EndpointType,
  options?: Partial<AxiosRequestConfig>
): AxiosInstance => {
  const config = {
    baseURL: `${process.env.VUE_APP_API_PATH}${endpoint}`,
    ...options,
  };
  const axiosInstance = Axios.create(config);

  // request interceptors are used to automatically attach the authorization header for endpoints
  // that require authorization
  axiosInstance.interceptors.request.use((axiosConfig) =>
    interceptorAuthHeader(axiosConfig, endpoint)
  );

  // response interceptors are used to catch errors globally to show a generic error message
  axiosInstance.interceptors.response.use(
    (response) => {
      /* add any response interceptors if desired */
      return response;
    },
    (error) => {
      console.error('axios error interceptor triggered.. :(');
      // TODO: show an error snackbar (but only for some endpoints)
      return Promise.reject(error);
    }
  );

  return axiosInstance;
};