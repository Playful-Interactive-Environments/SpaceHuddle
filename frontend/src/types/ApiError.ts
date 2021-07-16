interface ApiError {
  state?: string;
  errorMessage?: string,
  error?: {
    message: string;
    code: string;
    details: {
      message: string;
      field: string;
    }[];
  };
}

export default ApiError;
