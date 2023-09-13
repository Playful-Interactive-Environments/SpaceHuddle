import Color from 'colorjs.io';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function getHighlightColor(variation = ''): string {
  return getComputedStyle(document.body).getPropertyValue(
    `--color-highlight${variation}`
  );
}

export function getStructuringColor(variation = ''): string {
  return getComputedStyle(document.body).getPropertyValue(
    `--color-structuring${variation}`
  );
}

export function getBrainstormingColor(variation = ''): string {
  return getComputedStyle(document.body).getPropertyValue(
    `--color-brainstorming${variation}`
  );
}

export function getInformingColor(variation = ''): string {
  return getComputedStyle(document.body).getPropertyValue(
    `--color-informing${variation}`
  );
}

export function getPlayingColor(variation = ''): string {
  return getComputedStyle(document.body).getPropertyValue(
    `--color-playing${variation}`
  );
}

export function getEvaluatingColor(variation = ''): string {
  return getComputedStyle(document.body).getPropertyValue(
    `--color-evaluating${variation}`
  );
}

export function getContrastColor(variation = ''): string {
  return getComputedStyle(document.body).getPropertyValue(
    `--color-dark-contrast${variation}`
  );
}

export function getInactiveColor(): string {
  return getComputedStyle(document.body).getPropertyValue(
    '--color-gray-inactive'
  );
}

export function getGreenColor(): string {
  return getComputedStyle(document.body).getPropertyValue('--color-green');
}

export function getYellowColor(): string {
  return getComputedStyle(document.body).getPropertyValue('--color-yellow');
}

export function getRedColor(): string {
  return getComputedStyle(document.body).getPropertyValue('--color-red');
}

export function getBackgroundColor(): string {
  return getComputedStyle(document.body).getPropertyValue('--color-background');
}

export function convertToRGBA(hexColor: string, alpha = 1): string {
  const color = new Color(hexColor) as any;
  return `rgba(${Math.round(color.r * 256)}, ${Math.round(
    color.g * 256
  )}, ${Math.round(color.b * 256)}, ${alpha})`;
}
