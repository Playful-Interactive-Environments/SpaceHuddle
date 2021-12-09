import { apiExecuteGetHandled } from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea } from '@/types/api/Idea';
import { View } from '@/types/api/View';
import { OrderGroupList } from '@/types/api/OrderGroup';
import { convertToOrderGroups } from '@/services/idea-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

interface InputData {
  view: {
    type: string;
    id: string;
  };
  maxCount: number | null;
  filter: string[];
  order: string;
}

export const getDetail = async (
  type: string,
  typeId: string,
  orderType: string | null = null,
  refId: string | null = null,
  filter: string[] | null = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[]> => {
  let queryParameter = '';
  if (orderType) queryParameter = `?order=${orderType}`;
  if (refId && orderType) queryParameter = `${queryParameter}&refId=${refId}`;
  if (filter && filter.length > 0) {
    const filterString = `filter=${JSON.stringify(filter)}`;
    if (queryParameter.length > 0)
      queryParameter = `${queryParameter}&${filterString}`;
    else queryParameter = `?${filterString}`;
  }
  return await apiExecuteGetHandled<Idea[]>(
    `/${EndpointType.VIEW}/${type}/${typeId}/${queryParameter}`,
    [],
    authHeaderType
  );
};

export const getIdeas = async (
  input: InputData[],
  orderType: string | null = null,
  refId: string | null = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[]> => {
  const result: Idea[] = [];
  for (const index in input) {
    const item = input[index];
    await getDetail(
      item.view.type,
      item.view.id,
      orderType ? orderType : item.order,
      orderType ? refId : null,
      item.filter,
      authHeaderType
    ).then((inputResult) => {
      if (item.maxCount) inputResult = inputResult.slice(0, item.maxCount);
      result.push(...inputResult);
    });
  }
  if (input.length > 1)
    return result.filter(
      (item, index) => result.findIndex((item2) => item2.id == item.id) == index
    );
  return result;
};

export const getOrderGroups = async (
  input: InputData[],
  orderType: string | null = null,
  refId: string | null = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  actualOrderGroupList: OrderGroupList = {},
  filter: (idea) => boolean = () => {
    return true;
  }
): Promise<{ ideas: Idea[]; oderGroups: OrderGroupList }> => {
  let orderGroupList = {};
  let ideaList: Idea[] = [];
  await getIdeas(input, orderType, refId, authHeaderType).then((ideas) => {
    ideaList = ideas;
    orderGroupList = convertToOrderGroups(ideas, actualOrderGroupList, filter);
  });
  return { ideas: ideaList, oderGroups: orderGroupList };
};

export const getList = async (
  topicId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<View[]> => {
  return await apiExecuteGetHandled<View[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.VIEWS}`,
    [],
    authHeaderType
  );
};
