export function toDegrees(radians: number): number {
  return (radians / Math.PI) * 180;
}

export function toRadians(degrees: number): number {
  return (degrees / 180) * Math.PI;
}
