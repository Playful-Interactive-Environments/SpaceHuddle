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
  points: number;
}

export class ShoppingCalculation extends ResultCalculation {
  override calculateResult(answerList: {
    [key: string]: number | { [key: string]: number };
  }): ShoppingValue {
    const points = this.calculateResultPoints(answerList);
    return {
      points: points,
      resultType: this.calculateResultTypeValues(points),
    };
  }

  calculateResultPoints(answerList: {
    [key: string]: number | { [key: string]: number };
  }): number {
    return Object.values(answerList).reduce(
      (sum, item) => (sum as number) + (item as number),
      0
    ) as number;
  }

  calculateResultTypeValues(points: number): ShoppingType {
    if (points <= 15) {
      return ShoppingType.BARGAIN;
    } else if (points <= 20) {
      return ShoppingType.DELIBERATE;
    } else if (points <= 30) {
      return ShoppingType.IMPULSIVE;
    } else {
      return ShoppingType.BRANDS;
    }
  }
}
