export enum QuestionnaireType {
  QUIZ = 'quiz',
  SURVEY = 'survey',
}

export const moduleNameValid = (moduleName: string): boolean => {
  return moduleName === 'quiz' || moduleName === 'survey';
};
