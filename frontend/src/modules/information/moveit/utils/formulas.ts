import * as config from '@/modules/information/moveit/utils/configCalculation';

const airtight = 1.225;
const g = 9.81;
const rubberDense = 0.92;

/* eslint-disable @typescript-eslint/no-explicit-any*/
export const getTireArea = (vehicle: config.VehicleParameter): number => {
  const rationRubberToCracks = 2;
  const circumference = Math.PI * vehicle.tireDiameter;
  const area = circumference * vehicle.tireWidth;
  return area / rationRubberToCracks;
};

export const getTireWareMass = (
  vehicle: config.VehicleParameter,
  depth: number
): number => {
  const area = getTireArea(vehicle);
  const volume = area * depth;
  return volume * rubberDense;
};

export const tireWareRate = (
  speed: number,
  distance: number,
  vehicle: config.VehicleParameter
): number => {
  const rollingResistance =
    vehicle.rollingResistanceCoefficient * (vehicle.weight * g);
  //const rollingFrictionWork = rollingResistance * distance;
  const rollingFrictionPower = rollingResistance * speed;
  //const impulse = rollingResistance * time;
  const treadwareKilometer = 11500;
  const abrasionDepth =
    (rollingFrictionPower * (1 / vehicle.treadwear)) / treadwareKilometer;
  const mass = getTireWareMass(vehicle, abrasionDepth);
  return mass * vehicle.tires * distance * 1000; // convert from kilogram to gram
};

export const consumption = (
  speed: number,
  distance: number,
  vehicle: config.VehicleParameter
): number => {
  const fuel = config.fuelUnits(vehicle.fuel);
  const vehicleEfficiency = vehicle.efficiency
    ? vehicle.efficiency
    : fuel.efficiency;
  const airResistance =
    (1 / 2) *
    vehicle.flowResistance *
    airtight *
    (vehicle.profileArea / 1000) *
    Math.pow(speed, 2);
  const rollingResistance =
    vehicle.rollingResistanceCoefficient * (vehicle.weight * g);
  const power = ((airResistance + rollingResistance) * speed) / 1000;
  const fuelVolume = (power / fuel.kwh) * (1 / vehicleEfficiency);
  return (fuelVolume / 100) * distance;
};
