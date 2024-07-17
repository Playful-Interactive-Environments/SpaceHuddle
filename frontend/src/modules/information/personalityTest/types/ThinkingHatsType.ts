import surveyConfig from '@/modules/information/personalityTest/data/survey.json';
import {
  getResultTypeList,
  ResultCalculation,
  ResultValue,
} from '@/modules/information/personalityTest/types/ResultType';

export enum ThinkingHatsType {
  WHITE = 'white',
  BLUE = 'blue',
  RED = 'red',
  GREEN = 'green',
  YELLOW = 'yellow',
  BLACK = 'black',
}

export interface ThinkingHatsValue extends ResultValue {
  resultTypeValues: { [key: string]: number };
}

export class ThinkingHatsCalculation extends ResultCalculation {
  override calculateResult(answerList: {
    [key: string]: number | { [key: string]: number };
  }): ThinkingHatsValue {
    return {
      resultTypeValues: this.calculateResultTypeValues(answerList),
    };
  }
  calculateResultTypeValues(answerList: {
    [key: string]: number | { [key: string]: number };
  }): { [key: string]: number } {
    const result: { [key: string]: number } = {};
    const resultTypes = getResultTypeList('thinkingHats');
    for (const resultType of resultTypes) {
      result[resultType] = 0;
    }
    for (const question of surveyConfig.thinkingHats.questions) {
      for (const answer of question.options) {
        if (answerList[question.question][answer.answer]) {
          result[answer.resultType] +=
            answerList[question.question][answer.answer];
        }
      }
    }
    return result;
  }
}
