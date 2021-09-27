import jwt_decode from 'jwt-decode';
import app from '@/main';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

const JWT_KEY = 'jwt';
const JWT_KEY_MODERATOR = 'jwt-moderator';
const JWT_KEY_PARTICIPANT = 'jwt-participant';
const BROWSER_KEY = 'key';
const USER_KEY = 'user';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const isAuthenticated = (): boolean => {
  const jwtFromStorage = getAccessTokenModerator();
  const jwtFromStorageParticipant = getAccessTokenParticipant();
  return !!jwtFromStorage || !!jwtFromStorageParticipant;
};

const authorisationHasProperty = (
  propertyName: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): boolean => {
  const jwtFromStorage = getAccessToken(authHeaderType);
  if (jwtFromStorage != null) {
    const decoded = jwt_decode(jwtFromStorage) as any;
    if (decoded && propertyName in decoded) {
      return true;
    }
  }
  return false;
};

export const isParticipant = (): boolean => {
  return authorisationHasProperty(
    'participantId',
    EndpointAuthorisationType.PARTICIPANT
  );
};

export const isUser = (): boolean => {
  return authorisationHasProperty(
    'userId',
    EndpointAuthorisationType.MODERATOR
  );
};

export const getAccessToken = (
  authHeaderType = EndpointAuthorisationType.MODERATOR
): string | null => {
  switch (authHeaderType) {
    case EndpointAuthorisationType.MODERATOR:
      return getAccessTokenModerator();
    case EndpointAuthorisationType.PARTICIPANT:
      return getAccessTokenParticipant();
  }
  return app.config.globalProperties.$cookies.get(JWT_KEY);
};

export const setAccessToken = (jwt: string): void => {
  app.config.globalProperties.$cookies.set(JWT_KEY, 'Bearer ' + jwt);
};

export const setAccessTokenModerator = (jwt: string): void => {
  app.config.globalProperties.$cookies.set(JWT_KEY_MODERATOR, 'Bearer ' + jwt);
};

export const getAccessTokenModerator = (): string | null => {
  return app.config.globalProperties.$cookies.get(JWT_KEY_MODERATOR);
};

export const setAccessTokenParticipant = (jwt: string): void => {
  app.config.globalProperties.$cookies.set(
    JWT_KEY_PARTICIPANT,
    'Bearer ' + jwt
  );
};

export const getAccessTokenParticipant = (): string | null => {
  return app.config.globalProperties.$cookies.get(JWT_KEY_PARTICIPANT);
};

export const removeAccessToken = (): void => {
  //app.config.globalProperties.$cookies.remove(JWT_KEY_MODERATOR);
  //app.config.globalProperties.$cookies.remove(JWT_KEY_PARTICIPANT);
};

export const setBrowserKey = (key: string): void => {
  app.config.globalProperties.$cookies.set(BROWSER_KEY, key);
};

export const getBrowserKey = (): string | null => {
  return app.config.globalProperties.$cookies.get(BROWSER_KEY);
};

export const setUserData = (email: string): void => {
  app.config.globalProperties.$cookies.set(USER_KEY, email);
};

export const getUserData = (): string | null => {
  return app.config.globalProperties.$cookies.get(USER_KEY);
};
