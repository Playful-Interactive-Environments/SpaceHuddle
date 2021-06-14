import { apiEndpoint } from '@/services/api';

const API_SESSION_ENDPOINT = apiEndpoint('participant/');

export enum ConnectState {
  ACTIVE = 'ACTIVE',
  FAILED = 'Failed',
}

interface Avatar {
  color: string;
  symbol: string;
}

interface Participant {
  accessToken: string;
  avatar: Avatar;
  browserKey: string;
  id: string;
  ipHash: string;
  state: ConnectState;
}

export const connect = async (
  sessionKey: string
): Promise<Participant | Partial<Participant>> => {
  try {
    const { data } = await API_SESSION_ENDPOINT.post<Participant>('connect', {
      sessionKey,
      ipHash: 'asdfs',
    });
    // TODO: set browser-hash to localstorage?
    return data;
  } catch (error) {
    return error.response?.data;
  }
};

export const reconnect = async (): Promise<Participant> => {
  try {
    // TODO: browser hash from localstorage/sessionstorage?
    const browserKey = 'AHMS1JZT.9VZTRRNE';
    const { data } = await API_SESSION_ENDPOINT.get<Participant>(
      `connect/${browserKey}`
    );
    return data;
  } catch (e) {
    return e.response?.data;
  }
};
