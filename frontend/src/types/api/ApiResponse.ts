interface ApiResponse {
  message?: string;
  accessToken?: string;
  tokenType?: string;
  expiresIn?: string;
  parameter: any;
}

export default ApiResponse;
