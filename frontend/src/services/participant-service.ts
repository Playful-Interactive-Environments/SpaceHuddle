import { Topic } from '@/services/topic-service';
import { apiExecuteGetHandled, apiExecutePost} from '@/services/api';
import EndpointType from '@/types/EndpointType';
import EndpointAuthorisationType from "@/types/EndpointAuthorisationType";

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
  }
}

export const connect = async (
  sessionKey: string
): Promise<Participant | Partial<Participant>> => {
  return await apiExecutePost<Participant>(
    `/${EndpointType.PARTICIPANT_CONNECT}/`,
    {
      sessionKey,
    },
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const reconnect = async (browserKey: string): Promise<Participant> => {
  return await apiExecuteGetHandled<Participant>(
    `/${EndpointType.PARTICIPANT_RECONNECT}/${browserKey}/`,
    {},
    EndpointAuthorisationType.UNAUTHORISED
  );
};

export const getTopicList = async (
  authHeaderType = EndpointAuthorisationType.PARTICIPANT
): Promise<Topic[]> => {
  return await apiExecuteGetHandled<Topic[]>(
    `/${EndpointType.PARTICIPANT}/${EndpointType.TOPICS}/`,
    [],
    authHeaderType
  );
};
