interface ApiResponse {
  state: string;
  message: string;
}

interface LoginResponse {
  message: string;
  accessToken: string;
}

export { ApiResponse, LoginResponse };
