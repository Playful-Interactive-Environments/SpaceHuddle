import jwt_decode from 'jwt-decode';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Avatar } from '@/types/api/Participant';
import { useCookies } from 'vue3-cookies';
const { cookies } = useCookies();

const JWT_KEY = 'jwt';
const JWT_KEY_MODERATOR = 'jwt-moderator';
const JWT_KEY_PARTICIPANT = 'jwt-participant';
const BROWSER_KEYS = 'keys';
const LAST_BROWSER_KEY = 'key';
const LAST_AVATAR = 'avatar';
const USER_KEY = 'user';
const LOCALE = 'locale';

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

const getAuthorisationProperty = (
  propertyName: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): any => {
  const jwtFromStorage = getAccessToken(authHeaderType);
  if (jwtFromStorage != null) {
    const decoded = jwt_decode(jwtFromStorage) as any;
    if (decoded && propertyName in decoded) {
      return decoded[propertyName];
    }
  }
  return null;
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

export const getUserId = (): string => {
  return getAuthorisationProperty(
    'userId',
    EndpointAuthorisationType.MODERATOR
  ) as string;
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
  return cookies.get(JWT_KEY);
};

export const setAccessToken = (jwt: string): void => {
  cookies.set(JWT_KEY, 'Bearer ' + jwt);
};

export const setAccessTokenModerator = (jwt: string): void => {
  cookies.set(JWT_KEY_MODERATOR, 'Bearer ' + jwt);
};

export const getAccessTokenModerator = (): string | null => {
  return cookies.get(JWT_KEY_MODERATOR);
};

export const setAccessTokenParticipant = (jwt: string): void => {
  cookies.set(JWT_KEY_PARTICIPANT, 'Bearer ' + jwt);
};

export const getAccessTokenParticipant = (): string | null => {
  return cookies.get(JWT_KEY_PARTICIPANT);
};

export const removeAccessToken = (): void => {
  //cookies.remove(JWT_KEY_MODERATOR);
  //cookies.remove(JWT_KEY_PARTICIPANT);
};

export const removeAccessTokenModerator = (): void => {
  cookies.remove(JWT_KEY_MODERATOR);
};

export const setBrowserKey = (key: string): void => {
  cookies.set(LAST_BROWSER_KEY, key);
  const keys = getBrowserKeys();
  if (!keys.includes(key)) keys.push(key);
  cookies.set(BROWSER_KEYS, JSON.stringify(keys));
};

export const getBrowserKeys = (): string[] => {
  let keys = eval(cookies.get(BROWSER_KEYS));
  if (!keys || !Array.isArray(keys) || keys.length === 0) {
    const key = getLastBrowserKey();
    if (!key) keys = [];
    else keys = [key];
  }
  return keys;
};

export const setAvatar = (avatar: Avatar): void => {
  cookies.set(LAST_AVATAR, JSON.stringify(avatar));
};

export const getAvatar = (): Avatar => {
  return eval(cookies.get(LAST_AVATAR));
};

export const getLastBrowserKey = (): string | null => {
  return cookies.get(LAST_BROWSER_KEY);
};

export const setUserData = (email: string): void => {
  cookies.set(USER_KEY, email);
};

export const getUserData = (): string | null => {
  return cookies.get(USER_KEY);
};

export const setLocale = (locale: string): void => {
  if (cookies) cookies.set(LOCALE, locale);
};

export const getLocale = (): string | null => {
  if (cookies) return cookies.get(LOCALE);
  return null;
};
