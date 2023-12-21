import * as config from '@/modules/playing/moveit/utils/configCalculation';

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

const rollingResistance = (vehicle: config.VehicleParameter): number => {
  return vehicle.rollingResistanceCoefficient * (vehicle.weight * g);
};

const airResistance = (
  vehicle: config.VehicleParameter,
  speed: number
): number => {
  return (
    (1 / 2) *
    vehicle.flowResistance *
    airtight *
    (vehicle.profileArea / 1000) *
    Math.pow(speed, 2)
  );
};

export const tireWareRate = (
  speed: number,
  distance: number,
  vehicle: config.VehicleParameter
): number => {
  const rollingResistanceValue = rollingResistance(vehicle);
  /*const airResistanceValue = airResistance(vehicle, speed);
  const curveRadius = 25000;
  const centrifugalResistanceValue =
    (vehicle.weight * Math.pow(speed / 3.6, 2)) / curveRadius;
  const lateralResistanceValue =
    centrifugalResistanceValue + rollingResistanceValue;
  const exponent = 2;
  const calibrationFactor = 0.05;
  const lateralLongitudinalFactor = 7;
  const power =
    calibrationFactor *
    (Math.pow(rollingResistanceValue + airResistanceValue, exponent) +
      lateralLongitudinalFactor * Math.pow(lateralResistanceValue, exponent));
  const treadwareKilometer = 11500;
  const abrasionDepth = (power * (1 / vehicle.treadwear)) / treadwareKilometer;*/
  //const rollingFrictionWork = rollingResistance * distance;
  const rollingFrictionPower = rollingResistanceValue * speed;
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
  const airResistanceValue = airResistance(vehicle, speed);
  const rollingResistanceValue = rollingResistance(vehicle);
  const power = ((airResistanceValue + rollingResistanceValue) * speed) / 1000;
  const fuelVolume = (power / fuel.kwh) * (1 / vehicleEfficiency);
  return (fuelVolume / 100) * distance;
};
