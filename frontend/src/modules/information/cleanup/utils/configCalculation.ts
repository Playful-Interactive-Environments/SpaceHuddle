import * as gameConfig from '@/modules/information/cleanup/data/gameConfig.json';
import {
  TrackingData,
  ChartData,
} from '@/modules/information/cleanup/organisms/DriveToLocation.vue';

export enum FuelType {
  electricity = 'electricity',
  diesel = 'diesel',
  gasoline = 'gasoline',
  'gas' = 'gas',
}

export interface VehicleParameter {
  name: string;
  image: string;
  imageTop: string;
  fuel: FuelType;
  power: number | undefined;
  speed: number | undefined;
  mpg: number | undefined;
  acceleration: number | undefined;
  persons: number | undefined;
  efficiency: number | undefined;
  efficiencyV1: number | undefined;
  flowResistance: number;
  profileArea: number;
  weight: number;
  weightTire: number;
  rollingResistanceCoefficient: number;
  tires: number;
}

/* eslint-disable @typescript-eslint/no-explicit-any*/
export const getVehicleParameter = (
  vehicleCategory: string,
  vehicleType: string
): VehicleParameter => {
  const types = gameConfig.vehicles[vehicleCategory].types;
  const param = types.find((t) => t.name === vehicleType);
  param.tires = gameConfig.vehicles[vehicleCategory].tires;
  return param;
};

export const getFuelParameter = (fuel: FuelType): any => {
  return gameConfig.fuel[fuel].perUnit;
};

export const electricityUnits = (): any => {
  const perUnit = {
    carbonDioxide: 0,
    methane: 0,
    dust: 0,
    microplastic: 0,
  };
  for (const energySource of Object.keys(gameConfig.electricity)) {
    const energy = gameConfig.electricity[energySource];
    for (const particleSource of Object.keys(gameConfig.particles)) {
      perUnit[particleSource] +=
        (energy.perUnit[particleSource] * energy.value) / 100;
    }
  }
  return {
    carbonDioxide: perUnit.carbonDioxide,
    methane: perUnit.methane,
    dust: perUnit.dust,
    microplastic: perUnit.microplastic,
    kwh: 1,
    kg: 1,
    efficiency: gameConfig.fuel.electricity.perUnit.efficiency,
  };
};

export const fuelUnits = (fuel: FuelType): any => {
  if (fuel === FuelType.electricity) return electricityUnits();
  return gameConfig.fuel[fuel].perUnit;
};

export const statisticsValue = (
  particleName: string,
  trackingData: TrackingData,
  vehicleParameter: any
): number => {
  const particle = gameConfig.particles[particleName];
  const calculation = new Function(
    'trackingData',
    'parameter',
    'fuelValue',
    `return ${particle.calculation}`
  );

  const perUnit = fuelUnits(vehicleParameter.fuel);
  return (
    calculation(trackingData, vehicleParameter, perUnit[particleName]) /
    trackingData.persons
  );
  /*return (
    (trackingData.consumption * perUnit[particleName]) / trackingData.persons
  );*/
};

export const addValueToStatistics = (
  trackingData: TrackingData,
  vehicleParameter: any,
  chartData: ChartData
): void => {
  for (const particleName in gameConfig.particles) {
    const dataset = chartData.datasets.find((ds) => ds.name === particleName);
    if (dataset) {
      dataset.data = [
        ...dataset.data,
        statisticsValue(particleName, trackingData, vehicleParameter),
      ];
    }
  }
  chartData.labels.push(Math.round(trackingData.speed).toString());
};
