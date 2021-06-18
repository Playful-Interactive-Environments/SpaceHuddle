const JWT_KEY = 'jwt';

export const isAuthenticated = (): boolean => {
  const jwtFromStorage = getJwt();
  return !!jwtFromStorage;
};

export const setDebuggingJwt = (jwt: string): void => {
  window.localStorage.setItem('jwt', jwt);
};

export const getJwt = (): string | null => {
  return window.localStorage.getItem(JWT_KEY);
};
