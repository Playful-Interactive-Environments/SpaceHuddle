import * as constants from '@/modules/playing/moveit/utils/consts';
import { CalculationType, mapArrayToConstantSize } from '@/utils/statistic';
import { ChartData } from '@/modules/playing/moveit/organisms/DriveToLocation.vue';
import * as configCalculation from '@/modules/playing/moveit/utils/configCalculation';
import * as gameConfig from '@/modules/playing/moveit/data/gameConfig.json';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export interface TrackingData {
  speed: number;
  consumption: number;
  persons: number;
  distance: number;
  distanceTraveled: number;
  tireWareRate: number;
}

export function averageSpeed(trackingData: TrackingData[]): number {
  if (trackingData.length > 0) {
    const sum = trackingData.reduce((a, b) => a + b.speed, 0);
    return sum / trackingData.length;
  }
  return 0;
}

export function maxSpeed(trackingData: TrackingData[]): number {
  if (trackingData.length > 0) {
    return Math.max(...trackingData.map((item) => item.speed));
  }
  return 0;
}

export function consumption(trackingData: TrackingData[]): number {
  if (trackingData.length > 0) {
    return trackingData.reduce((a, b) => a + b.consumption, 0);
  }
  return 0;
}

export function distance(trackingData: TrackingData[]): number {
  if (trackingData.length > 0) {
    return trackingData.reduce((a, b) => a + b.distance, 0);
  }
  return 0;
}

export function persons(trackingData: TrackingData[]): number {
  if (trackingData.length > 0) {
    return Math.max(...trackingData.map((item) => item.persons));
  }
  return 0;
}

export function trackingDataToChartData(
  trackingData: TrackingData[],
  chartData: ChartData,
  vehicleParameter: any
): void {
  chartData.labels = trackingData.map((data) =>
    (Math.round(data.distanceTraveled * 100) / 100).toString()
  );
  for (const particleName in gameConfig.particles) {
    const dataset = chartData.datasets.find((ds) => ds.name === particleName);
    if (dataset) {
      dataset.data = trackingData.map((data) => {
        return configCalculation.statisticsValue(
          particleName,
          data,
          vehicleParameter
        );
      });
    }
  }
}

export function normalizedTrackingData(
  trackingData: TrackingData[],
  period = 1
): TrackingData[] {
  const normalizedTrackingData: TrackingData[] = [];
  const mappingLength = Math.round(constants.cleanupTime * period);
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
