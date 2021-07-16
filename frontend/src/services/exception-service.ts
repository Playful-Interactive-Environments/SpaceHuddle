import { AxiosError } from 'axios';
import ApiError from '@/types/ApiError';
import app from '@/main';
import { EventType } from '@/types/EventType';
import SnackbarType from '@/types/SnackbarType';
import HttpStatusCode from '@/types/HttpStatusCode ';
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

  newError.forEach((errorItem) => {
    if (errorList.find((error) => error === errorItem) === undefined)
      errorList.push(errorItem);
  });
  return errorList;
};

export const clearErrors = (errorList: string[]): string[] => {
  errorList.length = 0;
  return errorList;
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

    if (displayDBErrors) {
      if (errorResult && errorResult.error && errorResult.error.details) {
        const errorMessage: string[] = [];
        const errorList = errorResult.error.details;
        if (Array.isArray(errorList)) {
          errorList.forEach((item, i) => {
            addError(errorMessage, `${item.message}`);
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
