export function until(
  conditionFunction,
  maxWaitingTime: number | null = null,
  testInterval = 400
) {
  let waitingTime = 0;
  const poll = (resolve) => {
    if (conditionFunction()) resolve();
    else {
      if (!maxWaitingTime || maxWaitingTime > waitingTime) {
        waitingTime += testInterval;
        setTimeout(() => poll(resolve), testInterval);
      }
    }
  };

  return new Promise(poll);
}

export function delay(ms: number) {
  return new Promise((res) => setTimeout(res, ms));
}
