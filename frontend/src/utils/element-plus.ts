export function calculateMarks(
  start: number,
  end: number,
  stepDelta = 1,
  maxSteps = -1
): { [key: number]: string } {
  let list = Array.from(
    { length: (end - start) / stepDelta + 1 },
    (x, i) => i * stepDelta + start
  );
  if (maxSteps > 0 && list.length > maxSteps) {
    const rate = Math.ceil(list.length / maxSteps);
    const filteredList: number[] = [];
    for (let i = 0; i < list.length; i += rate) {
      filteredList.push(list[i]);
    }
    list = filteredList;
  }
  return Object.assign({}, ...list.map((v) => ({ [v]: `${v}` })));
}
