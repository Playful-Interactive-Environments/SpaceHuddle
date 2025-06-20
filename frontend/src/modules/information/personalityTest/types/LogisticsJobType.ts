import {
  ResultCalculation,
  ResultValue,
} from '@/modules/information/personalityTest/types/ResultType';
import surveyConfig from '@/modules/information/personalityTest/data/survey.json';

export enum LogisticsJobType {
  A = 'a',
  B = 'b',
  C = 'c',
  D = 'd',
  E = 'e',
  F = 'f',
  G = 'g',
  H = 'h',
  I = 'i',
  J = 'j',
  K = 'k',
}

export interface LogisticsJobsValue extends ResultValue {
  resultTypeValues: { [key: string]: number };
}

export class LogisticsJobCalculation extends ResultCalculation {
  override calculateResult(answerList: {
    [key: string]: number | { [key: string]: number };
  }): LogisticsJobsValue {
    return {
      resultTypeValues: this.calculateResultTypeValues(answerList),
    };
  }
  calculateResultTypeValues(answerList: {
    [key: string]: number | { [key: string]: number };
  }): { [key: string]: number } {
    const result: { [key: string]: number } = {};
    for (const question of surveyConfig.logisticsJob.questions) {
      if (question.resultType) {
        if (!result[question.resultType]) result[question.resultType] = 0;
        result[question.resultType] += answerList[question.question] as number;
      }
    }
    return result;
  }
}
