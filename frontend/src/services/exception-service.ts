import { AxiosError, AxiosResponse } from 'axios';
import ApiError from '@/types/api/ApiError';
import app from '@/main';
import HttpStatusCode from '@/types/enum/HttpStatusCode ';
import { removeAccessToken, isUser } from '@/services/auth-service';
import { ElMessage } from 'element-plus';

/* eslint-disable @typescript-eslint/no-explicit-any*/

const showLog = false;

export const getSingleErrorKey = (error: AxiosError): string => {
  if (
    error.response &&
    error.response.data &&
    error.response.data.errorMessage &&
    error.response.data.errorMessage.length > 0
  ) {
    return error.response.data.errorMessage[0];
  }
  return '';
};

export const getSingleTranslatedErrorMessage = (error: AxiosError): string => {
  const errorKey = getSingleErrorKey(error);
  if (errorKey.length > 0)
    return app.config.globalProperties.$i18n.translateWithFallback(errorKey);
  return '';
};

export const getErrorMessage = (error: AxiosError): string[] => {
  if (error.response && error.response.data && error.response.data.errorMessage)
    return error.response.data.errorMessage;
  return [];
};

export const addError = (
  errorList: string[],
  newError: string | string[],
  prefix = ''
): string[] => {
  if (!Array.isArray(newError)) newError = [newError];

  newError.forEach((errorItem: string) => {
    let errorMessage = `${prefix}${errorItem}`;
    if (!errorMessage.startsWith('error.'))
      errorMessage = `error.${errorMessage}`;
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
      if (part.length > 0 && /[a-z_]/.test(part)) {
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
    if (error.message == 'Network Error') {
      removeAccessToken();
      app.config.globalProperties.$router.push({
        name: 'home',
      });
    }
    return {};
  } else {
    errorResult = error.response?.data;
    const errorPrefix = calcErrorPrefix(response);

    if (displayDBErrors) {
      if (errorResult && errorResult.error && errorResult.error.details) {
        const errorMessage: string[] = [];
        const errorList = errorResult.error.details;
        if (Array.isArray(errorList)) {
          errorList.forEach((item) => {
            addError(
              errorMessage,
              `api.${errorPrefix}${item.field}.${item.message}`
            );
          });
        }

        reportErrors(errorMessage);
        errorResult.errorMessage = errorMessage;
      } else if (response.statusText) {
        const errorMessage: string[] = [
          `error.api.general.${response.statusText.replaceAll('.', '')}`,
        ];
        reportErrors(errorMessage);
      }
    }

    if (response.status == HttpStatusCode.UNAUTHORIZED) {
      removeAccessToken();
      app.config.globalProperties.$router.push({
        name: 'home',
      });
    }

    if (response.status == HttpStatusCode.FORBIDDEN) {
      app.config.globalProperties.$router.push({
        name: isUser() ? 'moderator-session-overview' : 'participant-overview',
      });
    }
  }
  return errorResult;
};

const reportErrors = (errors: string[]): void => {
  errors.forEach((error) => {
    ElMessage({
      message: app.config.globalProperties.$i18n.translateWithFallback(error),
      type: 'error',
      center: true,
      showClose: true,
    });
  });
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
