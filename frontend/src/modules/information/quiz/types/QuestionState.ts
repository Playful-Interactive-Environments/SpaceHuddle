export enum QuestionPhase {
  ANSWER = 'answer',
  DONE = 'done',
  RESULT = 'result',
}

export enum QuestionState {
  ACTIVE_CREATE_QUESTION = 'active_create_question',
  ACTIVE_WAIT_FOR_VOTE = 'active_wait_for_vote',
  ACTIVE_LAST_CHANGE = 'active_last_change',
  DONE_THANKS = 'done_thanks',
  DONE_WAIT = 'done_wait',
  RESULT_ANSWER = 'result_answer',
  RESULT_EXPLANATION = 'result_explanation',
  RESULT_STATISTICS = 'result_statistic',
}

export const QuizStateProperty: { [name: string]: { time: number } } = {
  [QuestionState.ACTIVE_CREATE_QUESTION]: { time: 0 },
  [QuestionState.ACTIVE_WAIT_FOR_VOTE]: { time: 10 },
  [QuestionState.ACTIVE_LAST_CHANGE]: { time: 0 },
  [QuestionState.DONE_THANKS]: { time: 10 },
  [QuestionState.DONE_WAIT]: { time: 0 },
  [QuestionState.RESULT_ANSWER]: { time: 10 },
  [QuestionState.RESULT_EXPLANATION]: { time: 10 },
  [QuestionState.RESULT_STATISTICS]: { time: 0 },
};
