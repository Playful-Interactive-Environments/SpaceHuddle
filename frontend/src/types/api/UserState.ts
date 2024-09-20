export interface UserState {
  id: string;
  username: string;
  creationDate: string;
  confirmed: boolean;
  ownSessions: number;
  sharedSessions: number;
}
