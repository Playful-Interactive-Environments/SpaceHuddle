import { AxiosError, AxiosResponse } from 'axios';
import ApiError from '@/types/api/ApiError';
import app from '@/main';
import HttpStatusCode from '@/types/enum/HttpStatusCode ';
import { removeAccessToken, isUser } from '@/services/auth-service';
import { ElMessage } from 'element-plus';
import i18n from '../i18n';

const { t } = i18n.global;

/* eslint-disable @typescript-eslint/no-explicit-any*/

const showLog = false;

export const getSingleErrorKey = (error: AxiosError): string => {
  const errorMessage = error.response?.data?.error?.details?.[0]?.message;
  if (errorMessage) {
    let field = error.response?.data?.error?.details?.[0]?.field as
      | string
      | undefined;
    field &&= field + '.';
    return `${field ?? ''}${errorMessage}`;
  }

  return error.response?.data?.errorMessage?.[0] ?? '';
};

export const getSingleTranslatedErrorMessage = (error: AxiosError): string => {
  const keyPrefix = calcErrorPrefix(error.response);
  const errorKey = getSingleErrorKey(error);
  return getErrorMessageWithFallback(errorKey, undefined, keyPrefix);
};

export const getErrorMessageWithFallback = (
  item: string,
  itemContent?: string[],
  prefix = ''
): string => {
  let translation = '';
  const translateParts = item.split(':');
  if (translateParts.length > 1) {
    const translateCode = translateParts[0];
    const fallbackMessage = translateParts[1].trim();
    translation = translateOrGetFallback(
      `error.api.${prefix}${translateCode}`,
      fallbackMessage,
      itemContent
    );
  } else {
    translation = translateOrGetFallback(
      `error.api.${prefix}${item}`,
      item,
      itemContent
    );
  }

  return translation;
};

const translateOrGetFallback = (
  key: string,
  fallback: string,
  itemContent?: string[]
) => {
  let translation = t(key) || fallback;

  if (translation.length === 0) {
    const keyParts = key.split('.');
    if (keyParts.length > 0) translation = keyParts[keyParts.length - 1];
  }

  if (itemContent && translation.length > 0) {
    let contentIndex: any;
    for (contentIndex in itemContent) {
      translation = translation.replace(
        new RegExp('\\{' + contentIndex + '\\}', 'g'),
        itemContent[contentIndex]
      );
    }
  }

  return translation;
};

export const getErrorMessage = (error: AxiosError): string[] => {
  if (
    error.response &&
    (error.response.data as any) &&
    (error.response.data as any).errorMessage
  )
    return (error.response.data as any).errorMessage;
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

    if (!errorList.includes(errorMessage)) {
      errorList.push(errorMessage);
    }
  });
  return errorList;
};

export const clearErrors = (errorList: string[]): string[] => {
  errorList.length = 0;
  return errorList;
};

const calcErrorPrefix = (response?: AxiosResponse): string => {
  let errorPrefix = '';

  const errorUrl = response?.config?.url;
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

  const errorMethode = response?.config.method;
  if (errorMethode) {
    errorPrefix += `${errorMethode}.`;
  }

  return errorPrefix;
};

let errorHistory: number[] = [];

export const apiErrorHandling = async (
  error: AxiosError,
  displayDBErrors = true
): Promise<ApiError> => {
  apiErrorLog(error);

  const response = error.response;
  let errorResult: any = {};
  if (!response) {
    if (error.message == 'Network Error') {
      const tzOffset = new Date().getTimezoneOffset() * 60000; //offset in milliseconds
      const localISOTime = new Date(Date.now() - tzOffset)
        .toISOString()
        .slice(0, -1);
      errorHistory = errorHistory.filter((item) => item > Date.now() - 2000);
      console.log(
        localISOTime,
        errorHistory.length,
        errorHistory.length > 0 ? errorHistory[0] - Date.now() : -1,
        error
      );
      /*if (errorHistory.length > 10) {
        errorHistory.length = 0;
        removeAccessToken();
        app.config.globalProperties.$router.push({
          name: 'home',
        });
      }*/
      errorHistory.push(Date.now());
    }
    return {};
  } else {
    errorResult = error.response?.data;
    const errorPrefix = calcErrorPrefix(response);

    if (displayDBErrors && window.location.pathname.length > 10) {
      if (errorResult && errorResult.error && errorResult.error.details) {
        const errorMessage: string[] = [];
        const errorList = errorResult.error.details;
        if (Array.isArray(errorList)) {
          errorList.forEach((item) => {
            let message = item.message as string;
            if (message.includes(':')) {
              message = message.split(':')[0];
            }
            addError(
              errorMessage,
              `api.${errorPrefix}${item.field}.${message}`
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

    if (response.status === HttpStatusCode.UNAUTHORIZED) {
      removeAccessToken();
      app.config.globalProperties.$router.push({
        name: 'home',
      });
    }

    if (response.status === HttpStatusCode.FORBIDDEN) {
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
      message: t(error),
      type: 'error',
      center: true,
      showClose: true,
    });
  });
};

export const apiErrorLog = async (error: AxiosError): Promise<void> => {
  if (showLog) {
    if (error.response) console.error(error.response.data.error.message);
    else console.error(error);
  }
  if (showLog && error.response && error.response.config) {
    console.log(
      `CATCH ${error.response.config.method}: ${error.response.config.url}`
    );
  }
};
