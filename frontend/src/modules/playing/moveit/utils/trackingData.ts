import * as constants from '@/modules/playing/moveit/utils/consts';
import { CalculationType, mapArrayToConstantSize } from '@/utils/statistic';

export interface TrackingData {
  speed: number;
  consumption: number;
  persons: number;
  distance: number;
  distanceTraveled: number;
  tireWareRate: number;
}

export function normalizedTrackingData(
  trackingData: TrackingData[]
): TrackingData[] {
  const normalizedTrackingData: TrackingData[] = [];
  const mappingLength = constants.cleanupTime;
  for (let i = 0; i < mappingLength; i++) {
    normalizedTrackingData[i] = {
      speed: mapArrayToConstantSize(
        trackingData,
        (item) => item.speed,
        i,
        mappingLength
      ),
      persons: mapArrayToConstantSize(
        trackingData,
        (item) => item.persons,
        i,
        mappingLength
      ),
      distance: mapArrayToConstantSize(
        trackingData,
        (item) => item.distance,
        i,
        mappingLength,
        CalculationType.Sum
      ),
      distanceTraveled: mapArrayToConstantSize(
        trackingData,
        (item) => item.distanceTraveled,
        i,
        mappingLength,
        CalculationType.Average
      ),
      tireWareRate: mapArrayToConstantSize(
        trackingData,
        (item) => item.tireWareRate,
        i,
        mappingLength,
        CalculationType.Sum
      ),
      consumption: mapArrayToConstantSize(
        trackingData,
        (item) => item.consumption,
        i,
        mappingLength,
        CalculationType.Sum
      ),
    };
  }
  return normalizedTrackingData;
}
