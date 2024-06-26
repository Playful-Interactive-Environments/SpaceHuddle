import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import { Avatar } from '@/types/api/Participant';
import TaskType from '@/types/enum/TaskType';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface TaskParticipantState {
  id: string;
  taskId: string;
  count: number;
  state: TaskParticipantStatesType;
  parameter: any;
  avatar: Avatar;
  iteration_count: number;
  iteration_done_count: number;
}

export interface TaskParticipantStateSum {
  taskId: string;
  name: string;
  taskType: TaskType;
  count: number;
}
