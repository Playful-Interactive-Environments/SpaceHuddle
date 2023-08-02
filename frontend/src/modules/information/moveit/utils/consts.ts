export const maxCleanupThreshold = 5;

export const calcChartHeight = (maxChartValue: number): number => {
  const height =
    maxChartValue > maxCleanupThreshold
      ? maxChartValue + 1
      : maxCleanupThreshold;
  if (height > 5) {
    return Math.ceil(height / 10) * 10;
  }
  return height;
};

export const convertFontSizeToScreenSize = (
  fontSize: number,
  gameWidth: number
): string => {
  if (gameWidth) {
    const divisionFactor = gameWidth / 750;
    return `${fontSize * divisionFactor}px`;
  }
  return `${fontSize}px`;
};
