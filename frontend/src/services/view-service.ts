import { apiExecuteGetHandled } from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea } from '@/types/api/Idea';
import { View, getViewName, getViewKey } from '@/types/api/View';
import { OrderGroupList } from '@/types/api/OrderGroup';
import { convertToOrderGroups, filterIdeas } from '@/services/idea-service';

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

const pushQueryParameter = (
  queryParameter: string,
  parameterString: string
): string => {
  if (queryParameter.length > 0) return `${queryParameter}&${parameterString}`;
  else return `?${parameterString}`;
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
  let queryParameter = '';
  if (orderType) queryParameter = `?order=${orderType}`;
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
  return await apiExecuteGetHandled<Idea[]>(
    `/${EndpointType.VIEW}/${type}/${typeId}/${queryParameter}`,
    [],
    authHeaderType
  );
};

export const getViewIdeas = async (
  topicId: string,
  input: InputData[],
  orderType: string | null = null,
  refId: string | null = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  $t: null | ((key: string) => string) = null,
  stateFilter: string[] = [],
  textFilter = ''
): Promise<Idea[]> => {
  const viewName = getList(topicId, authHeaderType);
  let viewNameList: View[] = [];
  viewName.then((list) => {
    viewNameList = list;
  });
  if (orderType) {
    const constOrderParts = orderType.split('&refId=');
    if (constOrderParts.length > 1) {
      refId = constOrderParts[1];
      orderType = constOrderParts[0];
    }
  }
  let result: Idea[] = [];
  const dbCalls: Promise<Idea[] | View[]>[] = [];
  for (const index in input) {
    const item = input[index];
    const orderParts = item.order.split('&refId=');
    let countRefId: string | null = null;
    let countOrderType: string | null = item.order;
    if (orderParts.length > 1) {
      countOrderType = orderParts[0];
      countRefId = orderParts[1];
    }
    const dbCall = getDetail(
      item.view.type,
      item.view.id,
      orderType ? orderType : countOrderType,
      orderType ? refId : countRefId,
      item.filter,
      item.maxCount,
      countOrderType,
      countRefId,
      authHeaderType
    );
    dbCall.then((inputResult) => {
      if (item.maxCount) inputResult = inputResult.slice(0, item.maxCount);
      result.push(...inputResult);
    });
    dbCalls.push(dbCall);
  }
  dbCalls.push(viewName);
  await Promise.all(dbCalls);
  for (const resultItem of result) {
    const inputItemView = viewNameList.find(
      (view) =>
        getViewKey(view).toLowerCase() === resultItem.orderGroup.toLowerCase()
    );
    if (inputItemView) {
      resultItem.orderGroup = getViewName(inputItemView, $t);
    }
  }
  result = filterIdeas(result, stateFilter, textFilter);
  const orderText = (idea: Idea): string => {
    return `${idea.orderGroup} ${idea.orderText} ${idea.id}`;
  };
  result = result.sort((a, b) => orderText(a).localeCompare(orderText(b)));
  if (input.length > 1)
    return result.filter(
      (item, index) => result.findIndex((item2) => item2.id == item.id) == index
    );
  return result;
};

export const getViewOrderGroups = async (
  topicId: string,
  input: InputData[],
  orderType: string | null = null,
  orderAsc = true,
  refId: string | null = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  actualOrderGroupList: OrderGroupList = {},
  $t: null | ((key: string) => string) = null,
  stateFilter: string[] = [],
  textFilter = '',
  filter: (idea) => boolean = () => {
    return true;
  }
): Promise<{ ideas: Idea[]; oderGroups: OrderGroupList }> => {
  let orderGroupList = {};
  let ideaList: Idea[] = [];
  await getViewIdeas(
    topicId,
    input,
    orderType,
    refId,
    authHeaderType,
    $t,
    stateFilter,
    textFilter
  ).then((ideas) => {
    ideaList = orderAsc ? ideas : ideas.reverse();
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
