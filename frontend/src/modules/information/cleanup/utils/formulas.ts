import * as config from '@/modules/information/cleanup/utils/configCalculation';

const airtight = 1.225;
const g = 9.1;

/* eslint-disable @typescript-eslint/no-explicit-any*/
export const acceleration = (
  speed: number,
  distance: number,
  vehicle: config.VehicleParameter
): number => {
  const airResistance =
    (1 / 2) *
    airtight *
    vehicle.flowResistance *
    (vehicle.profileArea / 1000) *
    Math.pow(speed, 2);
  const efficiencyCoefficient = vehicle.efficiency
    ? vehicle.efficiency / 1000
    : 1;
  const rollingResistance =
    vehicle.rollingResistanceCoefficient * (vehicle.weightTire * g) * 4;
  const fuelVolume =
    airResistance - efficiencyCoefficient * speed + rollingResistance;
  return (fuelVolume / 100) * distance;
  /*
  const time = distance / speed;
  const fuel = config.getFuelParameter(vehicle.fuel);
  const force =
    airResistance - efficiencyCoefficient * speed + rollingResistance;
  if (vehicle.fuel === config.FuelType.electricity) return force * time;*/
  /*const fuelMass = (1 / (fuel.calorific * 1000000)) * (force * 3600 * 1000);
  const fuelVolume = (1 / fuel.weight) * (fuelMass * 1000);
  const volumePerTime = fuelVolume * time;
  console.log(force, fuelVolume, fuelVolume / speed * 100, volumePerTime, speed, time * 3600, distance);//, force * (distance / 100), force, distance);
  return force * (distance / 100);*/
  /*const fuelVolume = (1 / fuel.kwh) * force;
  console.log(force, fuelVolume, fuelVolume * time);
  return fuelVolume * time;*/
};
