/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface Category {
  id: string;
  timestamp: string;
  description: string;
  keywords: string;
  parameter: any;
  image: string | null;
  link: string | null;
}