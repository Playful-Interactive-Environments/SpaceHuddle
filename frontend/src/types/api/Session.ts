import UserType from '@/types/enum/UserType';

export interface Session {
  connectionKey: string;
  creationDate: string;
  expirationDate: string;
  id: string;
  maxParticipants: number;
  description: string;
  publicScreenModuleId: string;
  role: UserType;
  title: string;
  topicCount: number;
  taskCount: number;
}

export interface SessionInfo {
  connectionKey: string;
  title: string;
}
