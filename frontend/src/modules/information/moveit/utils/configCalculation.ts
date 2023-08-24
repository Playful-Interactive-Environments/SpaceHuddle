import * as gameConfig from '@/modules/information/moveit/data/gameConfig.json';
import {
  TrackingData,
  ChartData,
} from '@/modules/information/moveit/organisms/DriveToLocation.vue';
import * as constants from '@/modules/information/moveit/utils/consts';
import * as vehicleCalculation from '@/modules/information/moveit/types/Vehicle';

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
  flowResistance: number;
  profileArea: number;
  weight: number;
  rollingResistanceCoefficient: number;
  tires: number;
  treadwear: number;
  tireDiameter: number;
  tireWidth: number;
}

/* eslint-disable @typescript-eslint/no-explicit-any*/
export const getVehicleParameter = (
  vehicle: vehicleCalculation.Vehicle
): VehicleParameter => {
  const types = gameConfig.vehicles[vehicle.category].types;
  const param = types.find((t) => t.name === vehicle.type);
  param.tires = gameConfig.vehicles[vehicle.category].tires;
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
    const energyValue = getElectricityValue(energySource);
    for (const particleSource of Object.keys(gameConfig.particles)) {
      perUnit[particleSource] +=
        (energy.perUnit[particleSource] * energyValue) / 100;
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

export const getElectricityValue = (energySource: string): number => {
  return globalThis.electricityProgress
    ? globalThis.electricityProgress[energySource].progress
    : gameConfig.electricity[energySource].value;
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
    (constants.playableReducingFactor * trackingData.persons)
  );
  /*return (
    (trackingData.consumption * perUnit[particleName]) / trackingData.persons
  );*/
};

export const addValueToStatistics = (
  trackingData: TrackingData,
  vehicleParameter: any,
  chartData: ChartData
): number => {
  let totalValue = 0;
  for (const particleName in gameConfig.particles) {
    const dataset = chartData.datasets.find((ds) => ds.name === particleName);
    if (dataset) {
      const particleValue = statisticsValue(
        particleName,
        trackingData,
        vehicleParameter
      );
      dataset.data = [...dataset.data, particleValue];
      totalValue += particleValue;
    }
  }
  chartData.labels.push(Math.round(trackingData.speed).toString());
  return totalValue;
};
