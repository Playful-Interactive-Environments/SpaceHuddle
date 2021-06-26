const JWT_KEY = 'jwt';
const BROWSER_KEY = 'key';

export const isAuthenticated = (): boolean => {
  const jwtFromStorage = getAccessToken();
  return !!jwtFromStorage;
};

export const setAccessToken = (jwt: string): void => {
  window.localStorage.setItem(JWT_KEY, 'Bearer ' + jwt);
};

export const getAccessToken = (): string | null => {
  return window.localStorage.getItem(JWT_KEY);
};

export const setBrowserKey = (key: string): void => {
  window.localStorage.setItem(BROWSER_KEY, key);
};

export const getBrowserKey = (): string | null => {
  return window.localStorage.getItem(BROWSER_KEY);
};
