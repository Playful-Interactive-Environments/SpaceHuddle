import {
  apiExecuteDelete,
  apiExecuteGetHandled,
  apiExecutePost,
  apiExecutePut,
} from '@/services/api';
import EndpointType from '@/types/enum/EndpointType';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Hierarchy } from '@/types/api/Hierarchy';
import { VoteResult } from '@/types/api/Vote';
import {
  getQuestionResultStorageFromHierarchy,
  QuestionResultStorage,
} from '@/modules/information/quiz/types/Question';
import * as votingService from '@/services/voting-service';
import * as ideaService from '@/services/idea-service';
import {deleteIdeaImage, itemImageChanged, putIdeaImage} from "@/services/idea-service";

/* eslint-disable @typescript-eslint/no-explicit-any*/

export const getById = async (
  id: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Hierarchy> => {
  return await apiExecuteGetHandled<Hierarchy>(
    `/${EndpointType.HIERARCHY}/${id}/`,
    {},
    authHeaderType
  );
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
  delete data.image;
  const imageChanged = data.id ? itemImageChanged(data.id, image) : false;
  console.log('putHierarchy');
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
  }
  return hierarchy;
};

export const getList = async (
  taskId: string,
  parentHierarchyId: string | null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<Hierarchy[]> => {
  const items = await apiExecuteGetHandled<Hierarchy[]>(
    `/${EndpointType.TASK}/${taskId}/${EndpointType.HIERARCHIES}/${parentHierarchyId}`,
    [],
    authHeaderType
  );
  return await getHierarchyImages(items, authHeaderType);
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

export const getHierarchyResult = async (
  taskId: string,
  parentHierarchyId: string | null,
  correctValue: string | null,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<VoteResult[]> => {
  let votes: VoteResult[] = [];
  await getList(taskId, parentHierarchyId, authHeaderType).then((answers) => {
    const result = answers
      .filter(
        (v, i, a) => a.findIndex((item) => item.keywords === v.keywords) === i
      )
      .sort((a, b) =>
        a.keywords < b.keywords ? -1 : a.keywords > b.keywords ? 1 : 0
      );
    votes = result.map((item) => {
      const count = answers.filter(
        (answer) => item.keywords === answer.keywords
      ).length;
      if (correctValue)
        item.parameter.isCorrect = item.keywords === correctValue.toString();
      return {
        idea: item,
        ratingSum: count,
        detailRatingSum: count,
        countParticipant: count,
      };
    });
  });
  return votes;
};

export const getParentResult = async (
  taskId: string,
  authHeaderType = EndpointAuthorisationType.MODERATOR
): Promise<VoteResult[]> => {
  const votes: VoteResult[] = await votingService.getParentResult(
    taskId,
    authHeaderType
  );
  const questions = await getList(
    taskId,
    '{parentHierarchyId}',
    authHeaderType
  );
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
        const questionResult = await getList(
          taskId,
          question.id,
          authHeaderType
        );
        const vote = votes.find((item) => item.idea.id === question.id);
        if (vote) {
          vote.ratingSum = questionResult.length;
          vote.detailRatingSum = questionResult.length;
          vote.countParticipant = questionResult.length;
        } else {
          votes.splice(questionIndex, 0, {
            idea: question,
            ratingSum: questionResult.length,
            detailRatingSum: questionResult.length,
            countParticipant: questionResult.length,
          });
        }
      }
    }
  }
  return votes;
};
