import Color from 'colorjs.io';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function getHighlightColor(): string {
  return getComputedStyle(document.body).getPropertyValue('--color-highlight');
}

export function getStructuringColor(): string {
  return getComputedStyle(document.body).getPropertyValue(
    '--color-structuring'
  );
}

export function getBrainstormingColor(): string {
  return getComputedStyle(document.body).getPropertyValue(
    '--color-brainstorming'
  );
}

export function getInformingColor(): string {
  return getComputedStyle(document.body).getPropertyValue('--color-informing');
}

export function getEvaluatingColor(): string {
  return getComputedStyle(document.body).getPropertyValue('--color-evaluating');
}

export function getContrastColor(): string {
  return getComputedStyle(document.body).getPropertyValue(
    '--color-dark-contrast'
  );
}

export function getInactiveColor(): string {
  return getComputedStyle(document.body).getPropertyValue(
    '--color-gray-inactive'
  );
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
