import { apiEndpoint } from '@/services/api';
import EndpointType from '@/types/Endpoint';

const API_SESSION_ENDPOINT = apiEndpoint(EndpointType.PARTICIPANT);

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
    const { data } = await API_SESSION_ENDPOINT.post<Participant>(
      EndpointType.CONNECT,
      {
        sessionKey,
        ip: 'asdfs', // TODO: send client ip
      }
    );
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
      `${EndpointType.CONNECT}/${browserKey}/`
    );
    return data;
  } catch (e) {
    return e.response?.data;
  }
};
