export enum QuestionnaireType {
  QUIZ = 'quiz',
  SURVEY = 'survey',
  QUIZSURVEYMIX = 'quizSurveyMix',
}

export const moduleNameValid = (moduleName: string): boolean => {
  return (
    moduleName === QuestionnaireType.QUIZ.toString() ||
    moduleName === QuestionnaireType.SURVEY.toString() ||
    moduleName === QuestionnaireType.QUIZSURVEYMIX.toString()
  );
};
