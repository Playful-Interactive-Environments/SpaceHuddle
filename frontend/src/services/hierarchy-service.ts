import {
  apiExecuteDelete,
  apiExecutePost,
  apiExecutePostHandled,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Hierarchy } from '@/types/api/Hierarchy';
import { VoteResult } from '@/types/api/Vote';
import {
  getQuestionResultStorageFromHierarchy,
  getQuestionTypeFromHierarchy,
  Question,
  QuestionResultStorage,
} from '@/modules/information/quiz/types/Question';
import * as ideaService from '@/services/idea-service';
import * as cashService from '@/services/cash-service';
import { deleteIdeaImage, itemImageChanged } from '@/services/idea-service';
import { Avatar } from '@/types/api/Participant';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const registerGetById = (
  id: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Hierarchy> => {
  return cashService.registerSimplifiedGet<Hierarchy>(
    `/${EndpointType.HIERARCHY}/${id}/`,
    callback,
    {},
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetById = (
  id: string,
  callback: (result: any) => void
): void => {
  cashService.deregisterGet(`/${EndpointType.HIERARCHY}/${id}/`, callback);
};

export const deleteHierarchy = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  confirmCheck = true
): Promise<boolean> => {
  return await apiExecuteDelete<any>(
    `/${EndpointType.HIERARCHY}/${id}/`,
    null,
    authHeaderType,
    confirmCheck
  );
};

export const postHierarchy = async (
  taskId: string,
  data: Partial<Hierarchy>,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Hierarchy> => {
  const image = data.image;
  data = { ...data };
  delete data.image;
  const hierarchy = await apiExecutePost<Hierarchy>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.HIERARCHY}`,
    data,
    authHeaderType
  );
  if (image && hierarchy.id) {
    hierarchy.image = image;
    await ideaService.putIdeaImage(
      { id: hierarchy.id, image: image },
      authHeaderType
    );
    ideaService.addIdeaImage(hierarchy.id, image, hierarchy.imageTimestamp);
  }
  return hierarchy;
};

export const putHierarchy = async (
  data: Partial<Hierarchy>,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Hierarchy> => {
  const image = data.image !== undefined ? data.image : null;
  data = { ...data };
  delete data.image;
  const imageChanged = data.id ? itemImageChanged(data.id, image) : false;
  const hierarchy = await apiExecutePut<Hierarchy>(
    `/${EndpointType.HIERARCHY}`,
    data,
    authHeaderType
  );
  if (imageChanged && hierarchy.id) {
    if (image) {
      hierarchy.image = image;
      await ideaService.putIdeaImage(
        { id: hierarchy.id, image: image },
        authHeaderType
      );
      ideaService.addIdeaImage(hierarchy.id, image, hierarchy.imageTimestamp);
    } else {
      await deleteIdeaImage(hierarchy.id, authHeaderType);
    }
  } else if (image) {
    hierarchy.image = image;
  }
  return hierarchy;
};

export const registerGetList = (
  taskId: string,
  parentHierarchyId: string | null,
  callback: (result: any, parentID: string | null) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Hierarchy[]> => {
  return cashService.registerSimplifiedGet<Hierarchy[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.HIERARCHIES}/${parentHierarchyId}`,
    callback,
    [],
    authHeaderType,
    maxDelaySeconds,
    async (items: Hierarchy[]) => {
      return await getHierarchyImages(items, authHeaderType);
    },
    [parentHierarchyId]
  );
};

export const refreshCash = (
  taskId: string,
  parentHierarchyId: string | null
): void => {
  cashService.refreshCash(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.HIERARCHIES}/${parentHierarchyId}`
  );
};

export const deregisterGetList = (
  taskId: string,
  parentHierarchyId: string | null,
  callback: (result: any, parentID: string | null) => void
): void => {
  return cashService.deregisterGet(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.HIERARCHIES}/${parentHierarchyId}`,
    callback
  );
};

export const registerGetQuestions = (
  taskId: string,
  callback: (result: any) => void,
  authHeaderType = EndpointAuthorisationType.MODERATOR,
  maxDelaySeconds = 60 * 5
): cashService.SimplifiedCashEntry<Hierarchy[]> => {
  return registerGetList(
    taskId,
    '{parentHierarchyId}',
    callback,
    authHeaderType,
    maxDelaySeconds
  );
};

export const deregisterGetQuestions = (
  taskId: string,
  callback: (result: any) => void
): void => {
  deregisterGetList(taskId, '{parentHierarchyId}', callback);
};

export const convertToQuestions = (items: Hierarchy[]): Question[] => {
  const result: Question[] = [];
  for (const index in items) {
    const question = items[index];
    const item: Question = {
      questionType: getQuestionTypeFromHierarchy(question),
      question: question,
      answers: [],
    };
    result.push(item);
  }
  return result;
};

export const getHierarchyImages = async (
  items: Hierarchy[],
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Hierarchy[]> => {
  return (await ideaService.getItemImages(
    items,
    authHeaderType
  )) as Hierarchy[];
};

export const getHierarchyResult = (
  answers: Hierarchy[],
  correctValue: string | null
): VoteResult[] => {
  let votes: VoteResult[] = [];
  const result = answers
    .filter(
      (v, i, a) => a.findIndex((item) => item.keywords === v.keywords) === i
    )
    .sort((a, b) =>
      a.keywords < b.keywords ? -1 : a.keywords > b.keywords ? 1 : 0
    );
  votes = result.map((item) => {
    const duplicates = answers.filter(
      (answer) => item.keywords === answer.keywords
    );
    const count = duplicates.length;
    if (correctValue)
      item.parameter.isCorrect = item.keywords === correctValue.toString();
    return {
      idea: item,
      ratingSum: count,
      detailRatingSum: count,
      countParticipant: count,
      avatarList: duplicates
        .filter((answer) => answer.avatar)
        .map((answer) => answer.avatar as Avatar),
    };
  });
  return votes;
};

export const getParentResult = (
  votes: VoteResult[],
  questions: Hierarchy[]
): VoteResult[] => {
  for (
    let questionIndex = 0;
    questionIndex < questions.length;
    questionIndex++
  ) {
    const question = questions[questionIndex];
    if (question.id) {
      const questionResultStorage: QuestionResultStorage =
        getQuestionResultStorageFromHierarchy(question);
      if (questionResultStorage === QuestionResultStorage.CHILD_HIERARCHY) {
        const vote = votes.find((item) => item.idea.id === question.id);
        if (vote) {
          vote.ratingSum = question.childCount;
          vote.detailRatingSum = question.childCount;
          vote.countParticipant = question.childCount;
        } else {
          votes.splice(questionIndex, 0, {
            idea: question,
            ratingSum: question.childCount,
            detailRatingSum: question.childCount,
            countParticipant: question.childCount,
            avatarList: question.avatar ? [question.avatar] : [],
          });
        }
      }
    }
  }
  return votes;
};

export const clone = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Hierarchy> => {
  return apiExecutePostHandled<Hierarchy>(
    `/${EndpointType.HIERARCHY}/${id}/clone`,
    null,
    null,
    authHeaderType
  );
};
