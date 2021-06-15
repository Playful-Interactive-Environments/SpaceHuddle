export const isAuthenticated = (): boolean => {
  return true;
};

let jwt = '';

export const setDebuggingJwt = (customJwt: string): void => {
  jwt = customJwt;
  window.localStorage.setItem('jwt', jwt);
};

export const getJwt = (): string | null => {
  const jwtFromStorage = window.localStorage.getItem('jwt');
  if (jwtFromStorage) return jwtFromStorage;
  return null;
};
