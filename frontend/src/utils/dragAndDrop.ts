/* eslint-disable @typescript-eslint/no-explicit-any*/

// eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
export const elStepDragDone = (event: any, activeIndex: number): number => {
  if (activeIndex === event.oldIndex) {
    activeIndex = event.newIndex;
  } else if (activeIndex < event.oldIndex && activeIndex >= event.newIndex) {
    activeIndex++;
  } else if (activeIndex > event.oldIndex && activeIndex <= event.newIndex) {
    activeIndex--;
  }
  return activeIndex;
};
