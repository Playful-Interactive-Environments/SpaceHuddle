import { Task } from '@/types/api/Task';

export interface Topic {
  id: string;
  title: string;
  description: string;
  activeTaskId: string;
  tasks?: Task[];
}