export enum QuizState {
  ACTIVE_CREATE_QUESTION,
  ACTIVE_WAIT_FOR_VOTE,
  ACTIVE_LAST_CHANGE,
  DONE_THANKS,
  DONE_WAIT,
  RESULT_ANSWER,
  RESULT_EXPLANATION,
  RESULT_STATISTICS,
}

export const QuizStateProperty: { [name: string]: { time: number } } = {
  [QuizState.ACTIVE_CREATE_QUESTION]: { time: 0 },
  [QuizState.ACTIVE_WAIT_FOR_VOTE]: { time: 10 },
  [QuizState.ACTIVE_LAST_CHANGE]: { time: 0 },
  [QuizState.DONE_THANKS]: { time: 10 },
  [QuizState.DONE_WAIT]: { time: 0 },
  [QuizState.RESULT_ANSWER]: { time: 5 },
  [QuizState.RESULT_EXPLANATION]: { time: 5 },
  [QuizState.RESULT_STATISTICS]: { time: 0 },
};
