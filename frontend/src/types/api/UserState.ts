export interface UserState {
  id: string;
  username: string;
  confirmed: boolean;
  ownSessions: number;
  sharedSessions: number;
}
