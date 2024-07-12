import surveyConfig from '@/modules/information/personalityTest/data/survey.json';
import {
  ResultCalculation,
  ResultValue,
} from '@/modules/information/personalityTest/types/ResultType';

export enum Big5Type {
  EXTRAVERSION = 'Extraversion',
  CONSCIENTIOUSNESS = 'Conscientiousness',
  NEUROTICISM = 'Neuroticism',
  OPENNESS = 'Openness',
  AGREEABLENESS = 'Agreeableness',
}

export interface Big5Value extends ResultValue {
  resultTypeValues: { [key: string]: number };
}

export class Big5Calculation extends ResultCalculation {
  override calculateResult(answerList: {
    [key: string]: number | { [key: string]: number };
  }): Big5Value {
    return {
      resultTypeValues: this.calculateResultTypeValues(answerList),
    };
  }
  calculateResultTypeValues(answerList: {
    [key: string]: number | { [key: string]: number };
  }): { [key: string]: number } {
    const result: { [key: string]: number } = {};
    for (const question of surveyConfig.big5.questions) {
      if (question.resultType) {
        if (!result[question.resultType]) result[question.resultType] = 0;
        if (question.reverse)
          result[question.resultType] +=
            6 - (answerList[question.question] as number);
        else
          result[question.resultType] += answerList[
            question.question
          ] as number;
      }
    }
    return result;
  }
}
