import {AxiosError, AxiosResponse} from 'axios';
import ApiError from '@/types/api/ApiError';
import app from '@/main';
import { EventType } from '@/types/enum/EventType';
import SnackbarType from '@/types/enum/SnackbarType';
import HttpStatusCode from '@/types/enum/HttpStatusCode ';
import { removeAccessToken } from '@/services/auth-service';

const showLog = false;

export const getErrorMessage = (error: AxiosError): string[] => {
  if (error.response && error.response.data && error.response.data.errorMessage)
    return error.response.data.errorMessage;
  return [];
};

export const addError = (errorList: string[], newError: string | string[]): string[] => {
  if (!Array.isArray(newError))
    newError = [newError];

  newError.forEach((errorItem: string) => {
    let errorMessage = errorItem;
    const errorParts = errorItem.split(':');
    if (errorParts.length > 1) {
      const errorCode = errorParts[0];
      const fallbackMessage = errorParts[1].trim();
      errorMessage = app.config.globalProperties.$i18n.t2(`error.${errorCode}`, fallbackMessage);
    }
    if (errorList.find((error) => error === errorMessage) === undefined)
      errorList.push(errorMessage);
  });
  return errorList;
};

export const clearErrors = (errorList: string[]): string[] => {
  errorList.length = 0;
  return errorList;
};

const calcErrorPrefix = (response: AxiosResponse): string => {
  let errorPrefix = '';

  const errorUrl = response.config?.url;
  let lastNamedUrlPart = '';
  if (errorUrl) {
    const errorUlrParts = errorUrl.split('/');
    errorUlrParts.forEach((part) => {
      if (part.length > 0 && !part.includes('-')) {
        lastNamedUrlPart = part;
      }
    });
  }
  errorPrefix += `${lastNamedUrlPart}.`;

  const errorMethode = response.config.method;
  if (errorMethode) {
    errorPrefix += `${errorMethode}.`;
  }

  return errorPrefix;
};

export const apiErrorHandling = async (
  error: AxiosError,
  displayDBErrors = true
): Promise<ApiError> => {
  apiErrorLog(error);

  const response = error.response;
  let errorResult: any = {};
  if (!response) {
    return {};
  } else {
    errorResult = error.response?.data;
    const errorPrefix = calcErrorPrefix(response);

    if (displayDBErrors) {
      if (errorResult && errorResult.error && errorResult.error.details) {
        const errorMessage: string[] = [];
        const errorList = errorResult.error.details;
        if (Array.isArray(errorList)) {
          errorList.forEach((item, i) => {
            addError(errorMessage, `${errorPrefix}${item.field}.${item.message}`);
          });
        }
        app.config.globalProperties.eventBus.emit(EventType.SHOW_SNACKBAR, {
          type: SnackbarType.ERROR,
          message: errorMessage,
        });

        errorResult.errorMessage = errorMessage;
      } else if (response.statusText) {
        app.config.globalProperties.eventBus.emit(EventType.SHOW_SNACKBAR, {
          type: SnackbarType.ERROR,
          message: response.statusText,
        });
      }
    }

    if (
      response.status == HttpStatusCode.UNAUTHORIZED ||
      response.status == HttpStatusCode.FORBIDDEN
    ) {
      removeAccessToken();
      app.config.globalProperties.$router.push({
        name: 'home',
      });
    }
  }
  return errorResult;
};

export const apiErrorLog = async (error: AxiosError): Promise<void> => {
  if (showLog) {
    if (error.response) console.error(error.response.data);
    else console.error(error);
  }
  if (showLog && error.response && error.response.config) {
    console.log(
      `CATCH ${error.response.config.method}: ${error.response.config.url}`
    );
  }
};
