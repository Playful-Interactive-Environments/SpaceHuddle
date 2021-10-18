/* eslint-disable @typescript-eslint/no-explicit-any*/

export const submitOnEnter = (event: KeyboardEvent): void => {
  if (event.ctrlKey && event.code === 'Enter') {
    (event.target as any).form.dispatchEvent(
      new Event('submit', { cancelable: true })
    );
    event.preventDefault(); // Prevents the addition of a new line in the text field (not needed in a lot of cases)
  }
};
