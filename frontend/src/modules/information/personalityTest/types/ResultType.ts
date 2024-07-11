import surveyConfig from '@/modules/information/personalityTest/data/survey.json';

export function getResultTypeList(test: string): string[] {
  if (surveyConfig[test]) {
    /*return Object.keys(surveyConfig[test].result).map((item) =>
      item.toLowerCase()
    );
    return Object.keys(surveyConfig[test].result);*/
    return surveyConfig[test].result;
  }
  return [];
}

// eslint-disable-next-line @typescript-eslint/no-empty-interface
export interface ResultValue {}

export class ResultCalculation {
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  calculateResult(answerList: {
    [key: string]: number | { [key: string]: number };
  }): ResultValue {
    return {};
  }
}
