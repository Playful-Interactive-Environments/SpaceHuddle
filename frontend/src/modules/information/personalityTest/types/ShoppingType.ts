import {
  ResultCalculation,
  ResultValue,
} from '@/modules/information/personalityTest/types/ResultType';

export enum ShoppingType {
  BARGAIN = 'bargain',
  DELIBERATE = 'deliberate',
  IMPULSIVE = 'impulsive',
  BRANDS = 'brands',
}

export interface ShoppingValue extends ResultValue {
  resultType: ShoppingType;
}

export class ShoppingCalculation extends ResultCalculation {
  override calculateResult(answerList: {
    [key: string]: number | { [key: string]: number };
  }): ShoppingValue {
    return {
      resultType: this.calculateResultTypeValues(answerList),
    };
  }

  calculateResultTypeValues(answerList: {
    [key: string]: number | { [key: string]: number };
  }): ShoppingType {
    const sum = Object.values(answerList).reduce(
      (sum, item) => (sum as number) + (item as number),
      0
    );
    if (sum <= 15) {
      return ShoppingType.BARGAIN;
    } else if (sum <= 20) {
      return ShoppingType.DELIBERATE;
    } else if (sum <= 30) {
      return ShoppingType.IMPULSIVE;
    } else {
      return ShoppingType.BRANDS;
    }
  }
}
