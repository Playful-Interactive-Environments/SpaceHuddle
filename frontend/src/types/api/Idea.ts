/* eslint-disable @typescript-eslint/no-explicit-any*/

import { Avatar } from '@/types/api/Participant';

export interface Idea {
  id: string;
  state: string;
  timestamp: string;
  description: string;
  keywords: string;
  image: string | null;
  link: string | null;
  order: string;
  avatar: Avatar | null;
  category: { id: string; name: string; parameter: any };
  parameter: any;
}