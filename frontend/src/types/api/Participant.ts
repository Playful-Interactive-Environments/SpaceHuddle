export enum ConnectState {
  ACTIVE = 'ACTIVE',
  FAILED = 'Failed',
}

export interface Avatar {
  color: string;
  symbol: string;
}

export interface Participant {
  participant: {
    id: string;
    browserKey: string;
    state: ConnectState;
    avatar: Avatar;
    nickname: string;
  };
  token: {
    message: string;
    accessToken: string;
    tokenType: string;
    expiresIn: number;
  };
}

export interface ParticipantInfo {
  id: string;
  browserKey: string;
  state: ConnectState;
  avatar: Avatar;
  ideaCount: number;
  nickname: string;
}
