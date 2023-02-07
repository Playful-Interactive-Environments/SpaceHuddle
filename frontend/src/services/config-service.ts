import Axios, { AxiosInstance, AxiosRequestConfig } from 'axios';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const endpoint = (
  baseURL = `${process.env.VUE_APP_API_PATH}`,
  options?: Partial<AxiosRequestConfig>
): AxiosInstance => {
  const config = {
    baseURL: baseURL,
    headers: {
      'accept-language': 'de-AT,de-DE;q=0.9,de;q=0.8,en-US;q=0.7,en;q=0.6',
    },
    ...options,
  };
  if (!config.baseURL.endsWith('/')) {
    config.baseURL = `${config.baseURL}/`;
  }
  const axiosInstance = Axios.create(config);

  // response interceptors are used to catch errors globally to show a generic error message
  axiosInstance.interceptors.response.use(
    (response) => {
      /* add any response interceptors if desired */
      return response;
    },
    (error) => {
      return Promise.reject(error);
    }
  );

  return axiosInstance;
};

export async function apiExecuteGet<T = any>(url: string): Promise<T> {
  const baseUrl = window.location.origin;
  const { data: result } = await endpoint(baseUrl).get<T>(url);
  return result;
}
