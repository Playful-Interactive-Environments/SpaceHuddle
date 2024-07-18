import surveyConfig from '@/modules/information/personalityTest/data/survey.json';
import {
  getResultTypeList,
  ResultCalculation,
  ResultValue,
} from '@/modules/information/personalityTest/types/ResultType';

export enum CommunicationType {
  APPEAL = 'appeal',
  RELATIONSHIP = 'relationship',
  SELF_REVELATION = 'self_revelation',
  FACTUAL_INFORMATION = 'factual_information',
}

export interface CommunicationValue extends ResultValue {
  resultTypeValues: { [key: string]: number };
}

export class CommunicationCalculation extends ResultCalculation {
  override calculateResult(answerList: {
    [key: string]: number | { [key: string]: number };
  }): CommunicationValue {
    return {
      resultTypeValues: this.calculateResultTypeValues(answerList),
    };
  }
  calculateResultTypeValues(answerList: {
    [key: string]: number | { [key: string]: number };
  }): { [key: string]: number } {
    const result: { [key: string]: number } = {};
    const resultTypes = getResultTypeList('communication');
    for (const resultType of resultTypes) {
      result[resultType] = 0;
    }
    for (const question of surveyConfig.communication.questions) {
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
