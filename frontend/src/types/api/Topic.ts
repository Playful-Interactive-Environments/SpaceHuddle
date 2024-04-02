import { Task } from '@/types/api/Task';
import TopicStates from '@/types/enum/TopicStates';

export interface Topic {
  id: string;
  sessionId: string;
  title: string;
  description: string;
  order: number;
  state: TopicStates;
  activeTaskId: string;
  tasks?: Task[];
}
