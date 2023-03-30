import { Avatar } from '@/types/api/Participant';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export interface TaskParticipantIterationStep {
  id: string;
  iteration: number;
  step: number;
  ideaId: string;
  state: TaskParticipantIterationStepStatesType;
  parameter: any;
  avatar: Avatar;
}
