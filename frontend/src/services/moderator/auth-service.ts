const JWT_KEY = 'jwt';

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
