export const reloadCollapseContent = async (
  openTabs: string[],
  oldTabs: string[],
  action: () => Promise<string[]>,
  reloadTabState = false
): Promise<string[]> => {
  let newTabs: string[] = [];
  await action().then((keys) => (newTabs = keys));
  return reloadCollapseTabs(openTabs, oldTabs, newTabs, reloadTabState);
};

export const reloadCollapseTabs = async (
  openTabs: string[],
  oldTabs: string[],
  newTabs: string[],
  reloadTabState = false
): Promise<string[]> => {
  if (reloadTabState) return newTabs;
  else {
    const addedKeys = newTabs.filter((item) => oldTabs.indexOf(item) < 0);
    return openTabs.concat(addedKeys);
  }
};
