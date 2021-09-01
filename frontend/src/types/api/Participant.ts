export enum ConnectState {
  ACTIVE = 'ACTIVE',
  FAILED = 'Failed',
}

interface Avatar {
  color: string;
  symbol: string;
}
export interface Participant {
  participant: {
    id: string;
    browserKey: string;
    state: ConnectState;
    avatar: Avatar;
  };
  token: {
    message: string;
    accessToken: string;
    tokenType: string;
    expiresIn: number;
  };
}
