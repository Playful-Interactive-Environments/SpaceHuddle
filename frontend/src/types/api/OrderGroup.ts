import { Idea } from '@/types/api/Idea';
import { Avatar } from '@/types/api/Participant';

export interface OrderGroup {
  ideas: Idea[];
  avatar: Avatar | null;
  color: string | null;
  displayCount: number;
}

export interface OrderGroupList {
  [name: string]: OrderGroup;
}