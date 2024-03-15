/* eslint-disable @typescript-eslint/no-explicit-any*/

import { Avatar } from '@/types/api/Participant';

export interface Hierarchy {
  id: string | null;
  parentId: string | null;
  timestamp: string | null;
  description: string | null;
  keywords: string;
  parameter: any | null;
  imageTimestamp: string | null;
  image: string | null;
  link: string | null;
  order: number | null;
  isOwn: boolean;
  childCount: number;
  avatar: Avatar | null;
}
