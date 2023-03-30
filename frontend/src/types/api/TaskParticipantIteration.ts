import { Avatar } from '@/types/api/Participant';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface TaskParticipantIteration {
  id: string;
  iteration: number;
  state: TaskParticipantIterationStatesType;
  parameter: any;
  avatar: Avatar;
}
