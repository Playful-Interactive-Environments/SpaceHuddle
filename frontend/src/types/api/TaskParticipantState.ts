import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import { Avatar } from '@/types/api/Participant';
import TaskType from '@/types/enum/TaskType';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface TaskParticipantState {
  id: string;
  count: number;
  state: TaskParticipantStatesType;
  parameter: any;
  avatar: Avatar;
}

export interface TaskParticipantStateSum {
  taskId: string;
  name: string;
  taskType: TaskType;
  count: number;
}
