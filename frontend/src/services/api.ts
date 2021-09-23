import Axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import {
  getAccessTokenModerator,
  getAccessTokenParticipant,
} from '@/services/auth-service';
import { apiErrorHandling } from '@/services/exception-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import app from '@/main';

/* eslint-disable @typescript-eslint/no-explicit-any*/

const interceptorAuthHeader = (
  axiosConfig: AxiosRequestConfig,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): AxiosRequestConfig => {
  if (authHeaderType != EndpointAuthorisationType.UNAUTHORISED) {
    let jwt: string | null = null;
    switch (authHeaderType) {
      case EndpointAuthorisationType.MODERATOR:
        jwt = getAccessTokenModerator();
        break;
      case EndpointAuthorisationType.PARTICIPANT:
        jwt = getAccessTokenParticipant();
        break;
    }
    if (!jwt) throw new Error('Missing Authentication Token');

    axiosConfig.headers = {
      Authorization: jwt,
    };
  }
  return axiosConfig;
};

export const endpoint = (
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  options?: Partial<AxiosRequestConfig>,
  baseURL = `${process.env.VUE_APP_API_PATH}`
): AxiosInstance => {
  const config = {
    baseURL: baseURL,
    ...options,
  };
  if (!config.baseURL.endsWith('/')) {
    config.baseURL = `${config.baseURL}/`;
  }
  const axiosInstance = Axios.create(config);

  // request interceptors are used to automatically attach the authorization header for endpoints
  // that require authorization
  axiosInstance.interceptors.request.use((axiosConfig) =>
    interceptorAuthHeader(axiosConfig, authHeaderType)
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

export const apiEndpoint = (
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  options?: Partial<AxiosRequestConfig>
): AxiosInstance => {
  return endpoint(authHeaderType, options, `${process.env.VUE_APP_API_PATH}`);
};

export const API_ENDPOINT_MODERATOR = apiEndpoint(
  EndpointAuthorisationType.MODERATOR
);
export const API_ENDPOINT_PARTICIPANT = apiEndpoint(
  EndpointAuthorisationType.PARTICIPANT
);
export const API_ENDPOINT_UNAUTHORISED = apiEndpoint(
  EndpointAuthorisationType.UNAUTHORISED
);

export const getApiEndpoint = (
  authHeaderType = EndpointAuthorisationType.MODERATOR
): AxiosInstance => {
  switch (authHeaderType) {
    case EndpointAuthorisationType.MODERATOR:
      return API_ENDPOINT_MODERATOR;
    case EndpointAuthorisationType.PARTICIPANT:
      return API_ENDPOINT_PARTICIPANT;
    case EndpointAuthorisationType.UNAUTHORISED:
      return API_ENDPOINT_UNAUTHORISED;
  }
  return API_ENDPOINT_UNAUTHORISED;
};

export async function apiExecuteGetHandled<T = any>(
  url: string,
  empty: T | any = {},
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<T> {
  try {
    return await apiExecuteGet(url, authHeaderType);
  } catch (error) {
    return empty as T;
  }
}

export async function apiExecuteGet<T = any>(
  url: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<T> {
  const { data: result } = await getApiEndpoint(authHeaderType).get<T>(url);
  return result;
}

export async function apiExecutePostHandled<T = any>(
  url: string,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  body: any,
  empty: T | any = {},
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<T> {
  try {
    return await apiExecutePost(url, body, authHeaderType);
  } catch (error) {
    return empty as T;
  }
}

export async function apiExecutePost<T = any>(
  url: string,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  body: any,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<T> {
  const { data: result } = await getApiEndpoint(authHeaderType).post<T>(
    url,
    body
  );
  return result;
}

export async function apiExecutePutHandled<T = any>(
  url: string,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  body: any,
  empty: T | any = {},
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<T> {
  try {
    return await apiExecutePut(url, body, authHeaderType);
  } catch (error) {
    return empty as T;
  }
}

export async function apiExecutePut<T = any>(
  url: string,
  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  body: any,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<T> {
  const { data: result } = await getApiEndpoint(authHeaderType).put<T>(
    url,
    body
  );
  return result;
}

export async function apiExecuteDeleteHandled<T = any>(
  url: string,
  body: any = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<boolean> {
  if (await deleteConfirmDialog()) {
    try {
      if (body)
        await getApiEndpoint(authHeaderType).delete<T>(url, { data: body });
      else await getApiEndpoint(authHeaderType).delete<T>(url);
      return true;
    } catch (error) {
      return false;
    }
  }
  return false;
}

export async function apiExecuteDelete<T = any>(
  url: string,
  body: any = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<boolean> {
  if (await deleteConfirmDialog()) {
    if (body)
      await getApiEndpoint(authHeaderType).delete<T>(url, { data: body });
    else await getApiEndpoint(authHeaderType).delete<T>(url);
    return true;
  }
  return false;
}

const deleteConfirmDialog = async (): Promise<boolean> => {
  let confirmClicked = false;
  await app.config.globalProperties
    .$confirm(
      app.config.globalProperties.$t('confirm.delete.message'),
      app.config.globalProperties.$t('confirm.delete.title'),
      {
        confirmButtonText: app.config.globalProperties.$t('confirm.delete.ok'),
        cancelButtonText: app.config.globalProperties.$t(
          'confirm.delete.cancel'
        ),
        type: 'warning',
        roundButton: true,
      }
    )
    .then(() => {
      confirmClicked = true;
    })
    .catch(() => {
      confirmClicked = false;
    });
  return confirmClicked;
};
