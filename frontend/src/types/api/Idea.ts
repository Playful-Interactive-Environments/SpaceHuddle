/* eslint-disable @typescript-eslint/no-explicit-any*/

import { Avatar } from '@/types/api/Participant';

export interface Idea {
  id: string;
  state: string;
  timestamp: string;
  description: string;
  keywords: string;
  image: string | null;
  imageTimestamp: string | null;
  link: string | null;
  order: number;
  orderGroup: string;
  orderText: string;
  avatar: Avatar[];
  category: { id: string; name: string; parameter: any };
  parameter: any;
  participantId: string | null;
  isOwn: boolean;
  count: number;
}

export interface IdeaImage {
  id: string;
  image: string | null;
  imageTimestamp: string | null;
}

export interface IdeaTimestamp {
  image_timestamp: string | null;
}

export const MAX_KEYWORDS_LENGTH = 60;
export const MAX_DESCRIPTION_LENGTH = 2000;
