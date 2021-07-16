import Axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import { getAccessToken } from '@/services/auth-service';
import { apiErrorHandling } from '@/services/exception-service';

const interceptorAuthHeader = (
  axiosConfig: AxiosRequestConfig,
  addAuthHeader = true
): AxiosRequestConfig => {
  if (addAuthHeader) {
    const jwt = getAccessToken();
    if (!jwt) throw new Error('Missing Authentication Token');

    axiosConfig.headers = {
      Authorization: jwt,
    };
  }
  return axiosConfig;
};

export const apiEndpoint = (
  addAuthHeader = true,
  options?: Partial<AxiosRequestConfig>
): AxiosInstance => {
  const config = {
    baseURL: `${process.env.VUE_APP_API_PATH}`,
    ...options,
  };
  if (!config.baseURL.endsWith('/')) {
    config.baseURL = `${config.baseURL}/`;
  }
  const axiosInstance = Axios.create(config);

  // request interceptors are used to automatically attach the authorization header for endpoints
  // that require authorization
  axiosInstance.interceptors.request.use((axiosConfig) =>
    interceptorAuthHeader(axiosConfig, addAuthHeader)
  );

  // response interceptors are used to catch errors globally to show a generic error message
  axiosInstance.interceptors.response.use(
    (response) => {
      /* add any response interceptors if desired */
      return response;
    },
    (error) => {
      apiErrorHandling(error);
      return Promise.reject(error);
    }
  );

  return axiosInstance;
};

export const API_ENDPOINT_WITH_AUTH_HEADER = apiEndpoint(true);
export const API_ENDPOINT_WITHOUT_AUTH_HEADER = apiEndpoint(false);

export const getApiEndpoint = (addAuthHeader = true): AxiosInstance => {
  if (addAuthHeader) return API_ENDPOINT_WITH_AUTH_HEADER;
  else return API_ENDPOINT_WITHOUT_AUTH_HEADER;
};

export async function apiExecuteGetHandled<T = any>(
  url: string,
  empty: T | any = {},
  addAuthHeader = true
): Promise<T> {
  try {
    return await apiExecuteGet(url, addAuthHeader);
  } catch (error) {
    return empty as T;
  }
}

export async function apiExecuteGet<T = any>(
  url: string,
  addAuthHeader = true
): Promise<T> {
  const { data: result } = await getApiEndpoint(addAuthHeader).get<T>(url);
  return result;
}

export async function apiExecutePostHandled<T = any>(
  url: string,
  body: any,
  empty: T | any = {},
  addAuthHeader = true
): Promise<T> {
  try {
    return await apiExecutePost(url, body, addAuthHeader);
  } catch (error) {
    return empty as T;
  }
}

export async function apiExecutePost<T = any>(
  url: string,
  body: any,
  addAuthHeader = true
): Promise<T> {
  const { data: result } = await getApiEndpoint(addAuthHeader).post<T>(
    url,
    body
  );
  return result;
}

export async function apiExecutePutHandled<T = any>(
  url: string,
  body: any,
  empty: T | any = {},
  addAuthHeader = true
): Promise<T> {
  try {
    return await apiExecutePut(url, body, addAuthHeader);
  } catch (error) {
    return empty as T;
  }
}

export async function apiExecutePut<T = any>(
  url: string,
  body: any,
  addAuthHeader = true
): Promise<T> {
  const { data: result } = await getApiEndpoint(addAuthHeader).put<T>(
    url,
    body
  );
  return result;
}

export async function apiExecuteDeleteHandled<T = any>(
  url: string,
  addAuthHeader = true
): Promise<void> {
  try {
    await apiExecuteDelete(url, addAuthHeader);
  } catch (error) {
    return;
  }
}

export async function apiExecuteDelete<T = any>(
  url: string,
  addAuthHeader = true
): Promise<void> {
  await getApiEndpoint(addAuthHeader).delete<T>(url);
}
