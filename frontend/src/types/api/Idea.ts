/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface Idea {
  id: string;
  state: string;
  timestamp: string;
  description: string;
  keywords: string;
  image: string | null;
  link: string | null;
  order: string;
  parameter: any;
}