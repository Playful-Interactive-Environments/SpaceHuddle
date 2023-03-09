import { Session } from '@/types/api/Session';
export class SessionOrderGroup {
  sessions: Session[] = [];

  constructor(sessions: Session[]) {
    this.sessions = sessions;
  }
}

export interface SessionOrderGroupList {
  [name: string]: SessionOrderGroup;
}

export interface SessionSortOrderOption {
  orderType: string;
}
