import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePostHandled,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea, IdeaImage, IdeaTimestamp } from '@/types/api/Idea';
import {
  OrderGroup,
  OrderGroupList,
  SortOrderOption,
} from '@/types/api/OrderGroup';
import IdeaSortOrder, {
  IdeaSortOrderHierarchy,
  DefaultDisplayCount,
  IdeaSortOrderView,
} from '@/types/enum/IdeaSortOrder';
import TaskType from '@/types/enum/TaskType';
import { Hierarchy } from '@/types/api/Hierarchy';
import * as cashService from '@/services/cash-service';
import { Task } from '@/types/api/Task';
const imageDB: IdeaImage[] = [];

/* eslint-disable @typescript-eslint/no-explicit-any*/

function fireEvent(
  name: string,
  target: EventTarget,
  param1: any,
  param2: any
): void {
  //Ready: create a generic event
  const evt = document.createEvent('Events');
  //Aim: initialize it to be the event we want
  evt.initEvent(name, true, true); //true for can bubble, true for cancelable
  (evt as any).param1 = param1;
  (evt as any).param2 = param2;
  //FIRE!
  target.dispatchEvent(evt);
}

export const deleteIdea = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  confirmCheck = true
): Promise<boolean> => {
  return await apiExecuteDelete<any>(
    `/${EndpointType.IDEA}/${id}/`,
    null,
    authHeaderType,
    confirmCheck
  );
};

export const deleteIdeaImage = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<void> => {
  await apiExecuteDelete<any>(
    `/${EndpointType.IDEA}/${id}/${EndpointType.IMAGE}/`,
    null,
    authHeaderType,
    false
  );
};

export const postIdea = async (
  taskId: string,
  data: Partial<Idea>,
  authHeaderType = EndpointAuthorisationType.PARTICIPANT
): Promise<Idea> => {
  const image = data.image;
  data = { ...data };
  delete data.image;
  const idea = await apiExecutePost<Idea>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.IDEA}`,
    data,
    authHeaderType
  );
  if (image) {
    idea.image = image;
    await putIdeaImage({ id: idea.id, image: image }, authHeaderType);
    //addIdeaImage(idea.id, image, idea.imageTimestamp);
  }
  return idea;
};

export const putIdea = async (
  data: Partial<Idea>,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  effectImages = true
): Promise<Idea> => {
  const image = data.image;
  data = { ...data };
  delete data.image;
  if (effectImages) {
    const dbItem = imageDB.find((db) => db.id === data.id);
    if ((dbItem && dbItem.image !== image) || (!dbItem && image)) {
      const dbCalls: Promise<void>[] = [];
      if (image)
        dbCalls.push(
          putIdeaImage({ id: data.id, image: image }, authHeaderType)
        );
      else if (data.id) dbCalls.push(deleteIdeaImage(data.id, authHeaderType));
    }
  }
  const idea = await apiExecutePut<Idea>(
    `/${EndpointType.IDEA}`,
    data,
    authHeaderType
  );
  if (image) {
    idea.image = image;
    //addIdeaImage(idea.id, image, idea.imageTimestamp);
  }
  return idea;
};

export const putIdeaImage = async (
  data: Partial<IdeaImage>,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<void> => {
  const result = await apiExecutePut<IdeaTimestamp>(
    `/${EndpointType.IDEA}/${EndpointType.IMAGE}/`,
    data,
    authHeaderType
  );
  if (data.id && data.image)
    addIdeaImage(data.id, data.image, result.image_timestamp);
};

const getIdeaImage = async (
  ideaId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<IdeaImage> => {
  return await apiExecuteGetHandled<IdeaImage>(
    `/${EndpointType.IDEA}/${ideaId}/${EndpointType.IMAGE}/`,
    null,
    authHeaderType
  );
};

export const getIdeaImages = async (
  ideas: Idea[],
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[]> => {
  return (await getItemImages(ideas, authHeaderType)) as Idea[];
};

export const getItemImages = async (
  ideas: Idea[] | Hierarchy[],
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[] | Hierarchy[]> => {
  const dbCalls: Promise<IdeaImage>[] = [];
  for (const idea of ideas) {
    if (idea.imageTimestamp && idea.id) {
      const dbItem = imageDB.find((db) => db.id === idea.id);
      if (dbItem) idea.image = dbItem.image;
      if (!dbItem || dbItem.imageTimestamp !== idea.imageTimestamp) {
        const dbCall = getIdeaImage(idea.id, authHeaderType);
        dbCall.then((imageResult) => {
          addIdeaImage(
            imageResult.id,
            imageResult.image,
            imageResult.imageTimestamp
          );
          idea.image = imageResult.image;
        });
        dbCalls.push(dbCall);
      }
    }
  }
  //await Promise.all(dbCalls);
  return ideas;
};

export const itemImageChanged = (id: string, image: string | null): boolean => {
  const dbItem = imageDB.find((db) => db.id === id);
  return !!((dbItem && dbItem.image !== image) || (!dbItem && image));
};

export const addIdeaImage = (
  id: string,
  image: string | null,
  imageTimestamp: string | null = null
): void => {
  const index = imageDB.findIndex((db) => db.id === id);
  if (index > -1) imageDB.splice(index, 1);
  imageDB.push({ id: id, image: image, imageTimestamp: imageTimestamp });
  fireEvent('imageLoaded', window, id, image);
};

export const getQueryParameter = (
  orderType: string | null = null,
  refId: string | null = null
): string => {
  let queryParameter = getIdeaListParameter(orderType, refId);
  if (queryParameter.length > 0) queryParameter = `?${queryParameter}`;
  return queryParameter;
};

export const getIdeaListParameter = (
  orderType: string | null = null,
  refId: string | null = null
): string => {
  let queryParameter = '';
  if (orderType) queryParameter = `order=${orderType}`;
  if (refId && orderType) queryParameter = `${queryParameter}&refId=${refId}`;
  return queryParameter;
};

export const registerGetIdea = (
  id: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Idea> => {
  return cashService.registerSimplifiedGet<Idea>(
    `/${EndpointType.IDEA}/${id}/`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (idea: Idea) => {
      return (await getIdeaImages([idea], authHeaderType))[0];
    }
  );
};

export const registerGetIdeasForTask = (
  taskId: string,
  orderType: string | null = null,
  refId: string | null = null,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Idea[]> => {
  const queryParameter = getQueryParameter(orderType, refId);
  return cashService.registerSimplifiedGet<Idea[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.IDEAS}/${queryParameter}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (ideas: Idea[]) => {
      return await getIdeaImages(ideas, authHeaderType);
    }
  );
};

export const deregisterGetIdeasForTask = (
  taskId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.IDEAS}/`,
    callback
  );
};

export const registerGetIdeasForTopic = (
  topicId: string,
  orderType: string | null = null,
  refId: string | null = null,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Idea[]> => {
  const queryParameter = getQueryParameter(orderType, refId);
  return cashService.registerSimplifiedGet<Idea[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.IDEAS}/${queryParameter}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (ideas: Idea[]) => {
      return await getIdeaImages(ideas, authHeaderType);
    }
  );
};

export const deregisterGetIdeasForTopic = (
  topicId: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.IDEAS}/`,
    callback
  );
};

export const getSortOrderOptions = (tasks: Task[]): SortOrderOption[] => {
  const ideaSortOrderOptions: SortOrderOption[] = Object.keys(
    IdeaSortOrder
  ).map((orderType) => {
    return { orderType: orderType.toLowerCase(), ref: null };
  });

  const categoryTasks = tasks.filter(
    (task) => task.taskType.toLowerCase() == TaskType.CATEGORISATION
  );
  if (categoryTasks) {
    categoryTasks.forEach((task) => {
      ideaSortOrderOptions.push({
        orderType: IdeaSortOrderHierarchy,
        ref: task,
      });
    });
  }
  const votingTasks = tasks.filter(
    (task) => task.taskType.toLowerCase() == TaskType.VOTING
  );
  if (votingTasks) {
    votingTasks.forEach((task) => {
      ideaSortOrderOptions.push({ orderType: IdeaSortOrderView, ref: task });
    });
  }
  return ideaSortOrderOptions;
};

export const convertToOrderGroups = (
  ideas: Idea[],
  actualOrderGroupList: OrderGroupList = {},
  filter: (idea) => boolean = () => {
    return true;
  },
  useOrderGroup = true
): OrderGroupList => {
  const orderGroupList = {};
  ideas
    .filter((idea) => filter(idea))
    .forEach((ideaItem) => {
      const orderKeyText = useOrderGroup
        ? ideaItem.orderGroup
        : ideaItem.orderText;
      if (orderKeyText) {
        const groupKey = !/^-?\d+$/.test(orderKeyText) //isNaN(parseInt(orderKeyText))
          ? orderKeyText
          : `${orderKeyText} `;
        const orderGroup = orderGroupList[groupKey];
        if (!orderGroup) {
          let displayCount = DefaultDisplayCount;
          if (groupKey in actualOrderGroupList)
            displayCount = actualOrderGroupList[groupKey].displayCount;
          let color = null;
          if (ideaItem.category) color = ideaItem.category.parameter.color;
          orderGroupList[groupKey] = new OrderGroup(
            [ideaItem],
            ideaItem.avatar,
            color,
            displayCount
          );
        } else {
          orderGroup.ideas.push(ideaItem);
        }
      }
    });
  return orderGroupList;
};

export const getOrderGroups = (
  ideas: Idea[],
  orderAsc = true,
  actualOrderGroupList: OrderGroupList = {},
  filter: (idea) => boolean = () => {
    return true;
  },
  useOrderGroup = true
): { ideas: Idea[]; oderGroups: OrderGroupList } => {
  const ideaList: Idea[] = orderAsc ? ideas : ideas.reverse();
  const orderGroupList = convertToOrderGroups(
    ideas,
    actualOrderGroupList,
    filter,
    useOrderGroup
  );
  return { ideas: ideaList, oderGroups: orderGroupList };
};

export const filterIdeas = (
  ideaList: Idea[],
  stateFilter: string[],
  textFilter: string
): Idea[] => {
  if (textFilter && textFilter.length > 0) {
    ideaList = ideaList.filter(
      (item) =>
        item.keywords.toLowerCase().includes(textFilter.toLowerCase()) ||
        (item.description &&
          item.description.toLowerCase().includes(textFilter.toLowerCase()))
    );
  }
  if (stateFilter && stateFilter.length > 0) {
    ideaList = ideaList.filter((item) => stateFilter.includes(item.state));
  }
  return ideaList;
};

export const clone = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea> => {
  return apiExecutePostHandled<Idea>(
    `/${EndpointType.IDEA}/${id}/clone`,
    null,
    null,
    authHeaderType
  );
};
