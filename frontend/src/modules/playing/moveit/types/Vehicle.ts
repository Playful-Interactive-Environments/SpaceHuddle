export interface Vehicle {
  category: 'car' | 'bike' | 'motorcycle' | 'scooter' | 'bus';
  type: string;
}

export function isSameVehicle(vehicle1: Vehicle, vehicle2: Vehicle): boolean {
  return (
    vehicle1.category === vehicle2.category && vehicle1.type === vehicle2.type
  );
}

export function isSameCategory(vehicle1: Vehicle, vehicle2: Vehicle): boolean {
  return vehicle1.category === vehicle2.category;
}

export function vehicleToString(vehicle: Vehicle): string {
  return `${vehicle.category} - ${vehicle.type}`;
}

export function vehicleCompare(vehicle1: Vehicle, vehicle2: Vehicle): number {
  const x = vehicleToString(vehicle1);
  const y = vehicleToString(vehicle2);
  if (x < y) return -1;
  if (x > y) return 1;
  return 0;
}
