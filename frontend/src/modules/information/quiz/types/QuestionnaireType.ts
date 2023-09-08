export enum QuestionnaireType {
  QUIZ = 'quiz',
  SURVEY = 'survey',
  TALK = 'talk',
}

export const moduleNameValid = (moduleName: string): boolean => {
  return (
    moduleName === QuestionnaireType.QUIZ.toString() ||
    moduleName === QuestionnaireType.SURVEY.toString() ||
    moduleName === QuestionnaireType.TALK.toString()
  );
};
