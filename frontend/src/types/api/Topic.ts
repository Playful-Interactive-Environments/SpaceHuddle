import { Task } from '@/types/api/Task';

export interface Topic {
  id: string;
  title: string;
  description: string;
  order: number;
  activeTaskId: string;
  tasks?: Task[];
}
