import UserType from '@/types/enum/UserType';
import { SessionVisibilityType } from '@/types/enum/SessionVisibilityType';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export interface Session {
  connectionKey: string;
  creationDate: string;
  expirationDate: string;
  id: string;
  maxParticipants: number | null;
  userCount: number;
  participantCount: number;
  description: string;
  publicScreenModuleId: string;
  role: UserType;
  title: string;
  subject: string | null;
  theme: string | null;
  topicActivation: string | null;
  topicCount: number;
  taskCount: number;
  allowAnonymous: boolean;
  parameter: any;
  visibility: SessionVisibilityType;
}

export interface SessionInfo {
  connectionKey: string;
  title: string;
}
