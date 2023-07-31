/* eslint-disable @typescript-eslint/no-explicit-any*/

export enum ConnectState {
  ACTIVE = 'ACTIVE',
  FAILED = 'Failed',
}

export interface Avatar {
  color: string;
  symbol: string;
}

export function convertAvatarToString(avatar: Avatar): string {
  return `${avatar.symbol}${avatar.color}`;
}

export interface Participant {
  participant: {
    id: string;
    browserKey: string;
    state: ConnectState;
    avatar: Avatar;
    parameter: any;
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
  voteCount: number;
}
