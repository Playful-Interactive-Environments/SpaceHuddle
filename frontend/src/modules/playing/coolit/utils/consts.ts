export const MIN_TEMPERATURE_RISE = -2;
export const MAX_TEMPERATURE_RISE = 4;

export function temperatureWinTime(
  winTime: number,
  temperatureRise: number
): number {
  //return winTime - Math.abs(temperatureRise * 30000);
  return winTime - temperatureRise * 30000;
}
