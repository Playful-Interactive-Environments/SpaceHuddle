import { Idea } from '@/types/api/Idea';
import { Avatar } from '@/types/api/Participant';
import { Task } from '@/types/api/Task';

export interface OrderGroup {
  ideas: Idea[];
  avatar: Avatar | null;
  color: string | null;
  displayCount: number;
}

export interface OrderGroupList {
  [name: string]: OrderGroup;
}

export interface SortOrderOption {
  orderType: string;
  ref: Task | null;
}
