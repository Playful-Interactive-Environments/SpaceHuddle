import surveyConfig from '@/modules/information/personalityTest/data/survey.json';
import {
  ResultCalculation,
  ResultValue,
} from '@/modules/information/personalityTest/types/ResultType';

export enum BrainhexType {
  ACHIEVER = 'Achiever',
  CONQUEROR = 'Conqueror',
  DAREDEVIL = 'Daredevil',
  MASTERMIND = 'Mastermind',
  SEEKER = 'Seeker',
  SOCIALISER = 'Socialiser',
  SURVIVOR = 'Survivor',
}

export interface BrainhexValue extends ResultValue {
  resultTypes: BrainhexType[];
  resultTypeExceptions: BrainhexType[];
  resultTypeValues: { [key: string]: number };
}

export class BrainhexCalculation extends ResultCalculation {
  override calculateResult(answerList: {
    [key: string]: number | { [key: string]: number };
  }): BrainhexValue {
    return {
      resultTypes: this.calculateResultType(answerList),
      resultTypeExceptions: this.calculateResultException(answerList),
      resultTypeValues: this.calculateResultTypeValues(answerList),
    };
  }
  calculateResultTypeValues(answerList: {
    [key: string]: number | { [key: string]: number };
  }): { [key: string]: number } {
    const result: { [key: string]: number } = {};
    for (const question of surveyConfig.brainhex.questions) {
      if (question.resultType) {
        if (!result[question.resultType]) result[question.resultType] = 0;
        result[question.resultType] +=
          (answerList[question.question] as number) - 3;
      } else if (question.options) {
        for (const answer of question.options) {
          result[answer.resultType] +=
            answerList[question.question][answer.answer];
        }
      }
    }
    return result;
  }

  calculateResultType(answerList: {
    [key: string]: number | { [key: string]: number };
  }): BrainhexType[] {
    const result: { [key: string]: number } =
      this.calculateResultTypeValues(answerList);
    return Object.keys(result).sort(
      (a, b) => result[b] - result[a]
    ) as BrainhexType[];
  }

  calculateResultException(answerList: {
    [key: string]: number | { [key: string]: number };
  }): BrainhexType[] {
    const result: { [key: string]: number } =
      this.calculateResultTypeValues(answerList);
    const exception: { [key: string]: number } = {};
    for (const key of Object.keys(result)) {
      if (result[key] < 0) exception[key] = result[key];
    }
    return Object.keys(exception).sort(
      (a, b) => exception[a] - exception[b]
    ) as BrainhexType[];
  }
}
