import Axios, { AxiosInstance, AxiosRequestConfig } from 'axios';
import {
  getAccessTokenModerator,
  getAccessTokenParticipant,
} from '@/services/auth-service';
import { apiErrorHandling } from '@/services/exception-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { EventType } from '@/types/enum/EventType';
import app from "@/main";

const interceptorAuthHeader = (
  axiosConfig: AxiosRequestConfig,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): AxiosRequestConfig => {
  if (authHeaderType != EndpointAuthorisationType.UNAUTHORISED) {
    let jwt = null;
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

export const apiEndpoint = (
  authHeaderType = EndpointAuthorisationType.MODERATOR,
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
  body: any,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<T> {
  const { data: result } = await getApiEndpoint(authHeaderType).put<T>(
    url,
    body
  );
  return result;
}

const DELETE_CALLBACK = 'deleteCallback';
export const declareDeleteCallback = async (): Promise<void> => {
  const declareEvents = (): void => {
    app.config.globalProperties.eventBus.off(DELETE_CALLBACK);
    app.config.globalProperties.eventBus.on(DELETE_CALLBACK, async (data: any) => {
        const input = data as {
          url: string;
          authHeaderType: EndpointAuthorisationType;
          handle: boolean;
        };
        if (input.handle) {
          try {
            await getApiEndpoint(input.authHeaderType).delete<any>(input.url);
          } catch (error) {
            return;
          }
        } else {
          await getApiEndpoint(input.authHeaderType).delete<any>(input.url);
        }
      }
    );
  };

  const loaded = (app != null && app.config != null);
  if (!loaded) {
    await setTimeout(() => {
      declareEvents();
    }, 500);
  } else {
    declareEvents();
  }
};

declareDeleteCallback();

export async function apiExecuteDeleteHandled<T = any>(
  url: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<void> {
  app.config.globalProperties.eventBus.emit(EventType.SHOW_CONFIRM, {
    message: 'confirm.delete.message',
    title: 'confirm.delete.title',
    callback: DELETE_CALLBACK,
    callbackData: { url: url, authHeaderType: authHeaderType, handle: true },
  });
  /*try {
    await apiExecuteDelete(url, authHeaderType);
  } catch (error) {
    return;
  }*/
}

export async function apiExecuteDelete<T = any>(
  url: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<void> {
  app.config.globalProperties.eventBus.emit(EventType.SHOW_CONFIRM, {
    message: 'confirm.delete.message',
    title: 'confirm.delete.title',
    callback: DELETE_CALLBACK,
    callbackData: { url: url, authHeaderType: authHeaderType, handle: false },
  });
  //await getApiEndpoint(authHeaderType).delete<T>(url);
}
