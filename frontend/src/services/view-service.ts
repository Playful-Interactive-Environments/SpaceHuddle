import { apiExecuteGetHandled } from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea } from '@/types/api/Idea';
import { View, getViewName, getViewKey } from '@/types/api/View';
import * as ideaService from '@/services/idea-service';
import * as cashService from '@/services/cash-service';

/* eslint-disable @typescript-eslint/no-explicit-any*/

const pushQueryParameter = (
  queryParameter: string,
  parameterString: string
): string => {
  if (queryParameter.length > 0) return `${queryParameter}&${parameterString}`;
  else return `?${parameterString}`;
};

const getDetailQueryParameter = (
  type: string,
  typeId: string,
  orderType: string | null = null,
  refId: string | null = null,
  filter: string[] | null = null,
  count: number | null,
  countOrderType: string | null = null,
  countRefId: string | null = null
): string => {
  let queryParameter = getDetailParameter(
    type,
    typeId,
    orderType,
    refId,
    filter,
    count,
    countOrderType,
    countRefId
  );
  if (queryParameter.length > 0) queryParameter = `?${queryParameter}`;
  return queryParameter;
};

export const getDetailParameter = (
  type: string,
  typeId: string,
  orderType: string | null = null,
  refId: string | null = null,
  filter: string[] | null = null,
  count: number | null,
  countOrderType: string | null = null,
  countRefId: string | null = null
): string => {
  let queryParameter = '';
  if (orderType) queryParameter = `order=${orderType}`;
  if (refId && orderType) queryParameter = `${queryParameter}&refId=${refId}`;
  if (filter && filter.length > 0) {
    const parameterString = `filter=${JSON.stringify(filter)}`;
    queryParameter = pushQueryParameter(queryParameter, parameterString);
  }
  if (count) {
    const parameterString = `count=${count}`;
    queryParameter = pushQueryParameter(queryParameter, parameterString);
  }
  if (countOrderType) {
    const parameterString = `countOrderType=${countOrderType}`;
    queryParameter = pushQueryParameter(queryParameter, parameterString);
  }
  if (countRefId) {
    const parameterString = `countRefId=${countRefId}`;
    queryParameter = pushQueryParameter(queryParameter, parameterString);
  }
  return queryParameter;
};

export const registerGetDetail = (
  type: string,
  typeId: string,
  orderType: string | null = null,
  refId: string | null = null,
  filter: string[] | null = null,
  count: number | null,
  countOrderType: string | null = null,
  countRefId: string | null = null,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Idea[]> => {
  const queryParameter = getDetailQueryParameter(
    type,
    typeId,
    orderType,
    refId,
    filter,
    count,
    countOrderType,
    countRefId
  );
  return cashService.registerSimplifiedGet<Idea[]>(
    `/${EndpointType.VIEW}/${type}/${typeId}/${queryParameter}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (ideas: Idea[]) => {
      return await ideaService.getIdeaImages(ideas, authHeaderType);
    }
  );
};

export const deregisterGetDetail = (
  type: string,
  typeId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.VIEW}/${type}/${typeId}/`,
    callback
  );
};

export const getDetail = async (
  type: string,
  typeId: string,
  orderType: string | null = null,
  refId: string | null = null,
  filter: string[] | null = null,
  count: number | null,
  countOrderType: string | null = null,
  countRefId: string | null = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[]> => {
  const queryParameter = getDetailQueryParameter(
    type,
    typeId,
    orderType,
    refId,
    filter,
    count,
    countOrderType,
    countRefId
  );
  const result = await apiExecuteGetHandled<Idea[]>(
    `/${EndpointType.VIEW}/${type}/${typeId}/${queryParameter}`,
    [],
    authHeaderType
  );
  return await ideaService.getIdeaImages(result, authHeaderType);
};

export const registerGetInputIdeas = (
  taskId: string,
  orderType: string | null = null,
  refId: string | null = null,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5,
  customKeyPrefix = ''
): cashService.SimplifiedCashEntry<Idea[]> => {
  const queryParameter = ideaService.getQueryParameter(orderType, refId);
  return cashService.registerSimplifiedGet<Idea[]>(
    `/${EndpointType.TASK}/${taskId}/input/${queryParameter}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (ideas: Idea[]) => {
      return await ideaService.getIdeaImages(ideas, authHeaderType);
    },
    null,
    customKeyPrefix
  );
};

export const deregisterGetInputIdeas = (
  taskId: string,
  callback: (result: any) => void,
  customKeyPrefix = ''
): void => {
  return cashService.deregisterGet(
    `${customKeyPrefix}/${EndpointType.TASK}/${taskId}/input/`,
    callback
  );
};

export const customizeView = (
  ideas: Idea[],
  views: View[] | null = null,
  $t: null | ((key: string) => string) = null,
  stateFilter: string[] = [],
  textFilter = '',
  inputLength = 1,
  useOrderGroup = true
): Idea[] => {
  if (views) {
    for (const resultItem of ideas) {
      const orderKeyText = useOrderGroup
        ? resultItem.orderGroup
        : resultItem.orderText;
      const inputItemView = views.find(
        (view) => getViewKey(view).toLowerCase() === orderKeyText.toLowerCase()
      );
      if (inputItemView) {
        resultItem.orderGroup = getViewName(inputItemView, $t);
      }
    }
  }
  ideas = ideaService.filterIdeas(ideas, stateFilter, textFilter);
  const orderText = (idea: Idea): string => {
    return `${idea.orderGroup} ${idea.orderText} ${idea.id}`;
  };
  ideas = ideas.sort((a, b) => orderText(a).localeCompare(orderText(b)));
  if (inputLength > 1) {
    return ideas.filter(
      (item, index) => ideas.findIndex((item2) => item2.id == item.id) == index
    );
  }

  return ideas;
};

export const registerGetList = (
  topicId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<View[]> => {
  return cashService.registerSimplifiedGet<View[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.VIEWS}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetList = (
  topicId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.VIEWS}`,
    callback
  );
};
