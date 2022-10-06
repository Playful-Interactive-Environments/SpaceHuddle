import { Hierarchy } from '@/types/api/Hierarchy';

export enum QuestionType {
  MULTIPLECHOICE = 'multiplechoice',
  SINGLECHOICE = 'singlechoice',
  RATING = 'rating',
  SLIDER = 'slider',
  NUMBER = 'number',
  TEXT = 'text',
  IMAGE = 'image',
}

export const SurveyQuestionType: QuestionType[] = [
  QuestionType.MULTIPLECHOICE,
  QuestionType.SINGLECHOICE,
  QuestionType.RATING,
  QuestionType.SLIDER,
  QuestionType.NUMBER,
  QuestionType.TEXT,
];
export const QuizQuestionType: QuestionType[] = [
  QuestionType.MULTIPLECHOICE,
  QuestionType.SINGLECHOICE,
  QuestionType.SLIDER,
  QuestionType.NUMBER,
];

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
  return QuestionType.MULTIPLECHOICE;
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
    questionType === QuestionType.MULTIPLECHOICE ||
    questionType === QuestionType.SINGLECHOICE
  )
    return QuestionResultStorage.VOTING;
  return QuestionResultStorage.CHILD_HIERARCHY;
};
