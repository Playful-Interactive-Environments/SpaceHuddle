import { Hierarchy } from '@/types/api/Hierarchy';

export enum QuestionType {
  MULTICHOICE = 'multichoice',
  SINGLECHOICE = 'singlechoice',
  RATING = 'rating',
  SLIDER = 'slider',
  NUMBER = 'number',
  TEXT = 'text',
}

export enum QuestionResultStorage {
  VOTING = 'voting',
  CHILD_HIERARCHY = 'child_hierarchy',
}

export interface Question {
  questionType: QuestionType;
  question: Hierarchy;
  answers: Hierarchy[];
}

export const getQuestionTypeFromHierarchy = (
  question: Hierarchy | null
): QuestionType => {
  if (question && question.parameter.questionType) {
    return question.parameter.questionType;
  }
  return QuestionType.MULTICHOICE;
};

export const getQuestionResultStorageFromHierarchy = (
  question: Hierarchy | null
): QuestionResultStorage => {
  return getQuestionResultStorageFromQuestionType(
    getQuestionTypeFromHierarchy(question)
  );
};

export const getQuestionResultStorageFromQuestionType = (
  questionType: QuestionType
): QuestionResultStorage => {
  if (
    questionType === QuestionType.MULTICHOICE ||
    questionType === QuestionType.SINGLECHOICE
  )
    return QuestionResultStorage.VOTING;
  return QuestionResultStorage.CHILD_HIERARCHY;
};
