/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface Idea {
  id: string;
  state: string; //what are the states of an idea??
  timestamp: string;
  description: string;
  keywords: string;
  image: string; //ignore at first?
  link: string; //link to where??
  order: string;
  parameter: any;
}