export const setHash = (hash: string): void => {
  const baseUrl = location.href.split('#')[0];
  location.replace(`${baseUrl}#${hash}`);
};
