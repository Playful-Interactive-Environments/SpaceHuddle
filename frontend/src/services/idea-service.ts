import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea } from '@/types/api/Idea';
import {
  OrderGroup,
  OrderGroupList,
  SortOrderOption,
} from '@/types/api/OrderGroup';
import IdeaSortOrder, {
  IdeaSortOrderCategorisation,
  DefaultDisplayCount,
} from '@/types/enum/IdeaSortOrder';
import * as taskService from '@/services/task-service';
import TaskType from '@/types/enum/TaskType';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const deleteIdea = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<boolean> => {
  return await apiExecuteDelete<any>(
    `/${EndpointType.IDEA}/${id}/`,
    null,
    authHeaderType
  );
};

export const postIdea = async (
  taskId: string,
  data: Partial<Idea>,
  authHeaderType = EndpointAuthorisationType.PARTICIPANT
): Promise<Idea> => {
  return await apiExecutePost<Idea>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.IDEA}`,
    data,
    authHeaderType
  );
};

export const putIdea = async (
  id: string,
  data: Partial<Idea>,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea> => {
  data['id'] = id;
  return await apiExecutePut<Idea>(
    `/${EndpointType.IDEA}`,
    data,
    authHeaderType
  );
};

export const getIdeasForTask = async (
  taskId: string,
  orderType: string | null = null,
  refId: string | null = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[]> => {
  let queryParameter = '';
  if (orderType) queryParameter = `?order=${orderType}`;
  if (refId && orderType) queryParameter = `${queryParameter}&refId=${refId}`;
  return await apiExecuteGetHandled<Idea[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.IDEAS}/${queryParameter}`,
    [],
    authHeaderType
  );
};

export const getIdeasForTopic = async (
  topicId: string,
  orderType: string | null = null,
  refId: string | null = null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Idea[]> => {
  let queryParameter = '';
  if (orderType) queryParameter = `?order=${orderType}`;
  if (refId && orderType) queryParameter = `${queryParameter}&refId=${refId}`;
  return await apiExecuteGetHandled<Idea[]>(
    `/${EndpointType.TOPIC}/${topicId}/${EndpointType.IDEAS}/${queryParameter}`,
    [],
    authHeaderType
  );
};

export const getSortOrderOptions = async (
  topicId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<SortOrderOption[]> => {
  const result: SortOrderOption[] = Object.keys(IdeaSortOrder).map(
    (orderType) => {
      return { orderType: orderType.toLowerCase(), ref: null };
    }
  );

  await taskService.getTaskList(topicId, authHeaderType).then((tasks) => {
    const categoryTasks = tasks.filter(
      (task) => task.taskType.toLowerCase() == TaskType.CATEGORISATION
    );
    if (categoryTasks) {
      categoryTasks.forEach((task) => {
        result.push({ orderType: IdeaSortOrderCategorisation, ref: task });
      });
    }
  });
  return result;
};

export const convertToOrderGroups = (
  ideas: Idea[],
  actualOrderGroupList: OrderGroupList = {},
  filter: (idea) => boolean = () => {
    return true;
  }
): OrderGroupList => {
  const orderGroupList = {};
  ideas
    .filter((idea) => filter(idea))
    .forEach((ideaItem) => {
      if (ideaItem.orderGroup) {
        const orderGroup = orderGroupList[ideaItem.orderGroup];
        if (!orderGroup) {
          let displayCount = DefaultDisplayCount;
          if (ideaItem.orderGroup in actualOrderGroupList)
            displayCount =
              actualOrderGroupList[ideaItem.orderGroup].displayCount;
          let color = null;
          if (ideaItem.category) color = ideaItem.category.parameter.color;
          orderGroupList[ideaItem.orderGroup] = new OrderGroup(
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

export const getOrderGroups = async (
  taskId: string,
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
  await getIdeasForTask(taskId, orderType, refId, authHeaderType).then(
    (ideas) => {
      ideaList = ideas;
      orderGroupList = convertToOrderGroups(
        ideas,
        actualOrderGroupList,
        filter
      );
    }
  );
  return { ideas: ideaList, oderGroups: orderGroupList };
};
