import surveyConfig from '@/modules/information/brainhex/data/survey.json';

export enum PlayerType {
  ACHIEVER = 'Achiever',
  CONQUEROR = 'Conqueror',
  DAREDEVIL = 'Daredevil',
  MASTERMIND = 'Mastermind',
  SEEKER = 'Seeker',
  SOCIALISER = 'Socialiser',
  SURVIVOR = 'Survivor',
}

export function calculatePlayerTypeValues(answerList: {
  [key: string]: number | { [key: string]: number };
}): { [key: string]: number } {
  const result: { [key: string]: number } = {};
  for (const question of surveyConfig.questions) {
    if (question.playerType) {
      if (!result[question.playerType]) result[question.playerType] = 0;
      result[question.playerType] +=
        (answerList[question.question] as number) - 3;
    } else if (question.options) {
      for (const answer of question.options) {
        result[answer.playerType] +=
          answerList[question.question][answer.answer];
      }
    }
  }
  return result;
}

export function calculatePlayerType(answerList: {
  [key: string]: number | { [key: string]: number };
}): PlayerType[] {
  const result: { [key: string]: number } =
    calculatePlayerTypeValues(answerList);
  return Object.keys(result).sort(
    (a, b) => result[b] - result[a]
  ) as PlayerType[];
}

export function calculatePlayerException(answerList: {
  [key: string]: number | { [key: string]: number };
}): PlayerType[] {
  const result: { [key: string]: number } =
    calculatePlayerTypeValues(answerList);
  const exception: { [key: string]: number } = {};
  for (const key of Object.keys(result)) {
    if (result[key] < 0) exception[key] = result[key];
  }
  return Object.keys(exception).sort(
    (a, b) => exception[a] - exception[b]
  ) as PlayerType[];
}
