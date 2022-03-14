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
  order: number;
  orderGroup: string;
  avatar: Avatar[];
  category: { id: string; name: string; parameter: any };
  parameter: any;
  participantId: string;
  isOwn: boolean;
  count: number;
}

export const MAX_KEYWORDS_LENGTH = 60;
export const MAX_DESCRIPTION_LENGTH = 1000;
