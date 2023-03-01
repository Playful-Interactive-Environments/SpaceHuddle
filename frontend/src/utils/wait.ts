export function until(conditionFunction) {
  const poll = (resolve) => {
    if (conditionFunction()) resolve();
    else setTimeout(() => poll(resolve), 400);
  };

  return new Promise(poll);
}

export function delay(ms: number) {
  return new Promise((res) => setTimeout(res, ms));
}
