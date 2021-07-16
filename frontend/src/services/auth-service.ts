import jwt_decode from 'jwt-decode';

const JWT_KEY = 'jwt';
const BROWSER_KEY = 'key';
const USER_KEY = 'user';

export const isAuthenticated = (): boolean => {
  const jwtFromStorage = getAccessToken();
  return !!jwtFromStorage;
};

const authorisationHasProperty = (propertyName: string): boolean => {
  const jwtFromStorage = getAccessToken();
  if (jwtFromStorage != null) {
    const decoded = jwt_decode(jwtFromStorage) as any;
    if (decoded && propertyName in decoded) {
      return true;
    }
  }
  return false;
};

export const isParticipant = (): boolean => {
  return authorisationHasProperty('participantId');
};

export const isUser = (): boolean => {
  return authorisationHasProperty('userId');
};

export const setAccessToken = (jwt: string): void => {
  window.localStorage.setItem(JWT_KEY, 'Bearer ' + jwt);
};

export const getAccessToken = (): string | null => {
  return window.localStorage.getItem(JWT_KEY);
};

export const removeAccessToken = (): void => {
  window.localStorage.removeItem(JWT_KEY);
};

export const setBrowserKey = (key: string): void => {
  window.localStorage.setItem(BROWSER_KEY, key);
};

export const getBrowserKey = (): string | null => {
  return window.localStorage.getItem(BROWSER_KEY);
};

export const setUserData = (email: string): void => {
  window.localStorage.setItem(USER_KEY, email);
};

export const getUserData = (): string | null => {
  return window.localStorage.getItem(USER_KEY);
};
