export function calculateMarks(
  start: number,
  end: number,
  stepDelta = 1,
  maxSteps = -1,
  first: number | null = null,
  last: number | null = null
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
  if (first !== null && !list.includes(first)) list.push(first);
  if (last !== null && !list.includes(last)) list.push(last);
  return Object.assign({}, ...list.map((v) => ({ [v]: `${v}` })));
}
