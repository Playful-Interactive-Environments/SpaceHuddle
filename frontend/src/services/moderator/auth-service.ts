export const isAuthenticated = (): boolean => {
  return true;
};

export const getDebuggingJwt = (): string => {
  return 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpYXQiOjE2MjM2NzU1ODAsImV4cCI6MTYyMzc2MTk4MCwiaXNzIjoiaHR0cHM6XC9cL2xvY2FsaG9zdFwvYXBpXC8iLCJkYXRhIjp7ImxvZ2luSWQiOiIyN2NkYjllOS1kYWFjLTQxODktODQ5YS0wMTJlZWZmNmI5NTYiLCJ1c2VybmFtZSI6Ik1hcnRpbmEyIn19.mTgiqWui6UUvm5FW-lefZdracKJ0FE6CTDGY1RqHnM0';
};

export const getJwt = (): string => {
  // TODO: replace with real authentication
  return getDebuggingJwt();
};
