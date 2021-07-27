import { Topic } from '@/types/api/Topic';
import { apiExecuteGetHandled, apiExecutePost} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from "@/types/enum/EndpointAuthorisationType";
import { Participant } from '@/types/api/Participant';

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
