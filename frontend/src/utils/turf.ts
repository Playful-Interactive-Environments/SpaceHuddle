import { FeatureCollection } from 'geojson';
import * as turf from '@turf/turf';

export enum DrivingDirection {
  forward,
  backward,
}

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function getRoute(
  routePath: FeatureCollection
): turf.Feature<turf.LineString> | turf.LineString {
  const pathPoints = (routePath.features[0].geometry as any).coordinates as [
    number,
    number
  ][];
  return turf.lineString(pathPoints);
}

export function getRouteStartPoint(
  routePath: FeatureCollection
): [number, number] {
  const pathPoints = (routePath.features[0].geometry as any).coordinates as [
    number,
    number
  ][];
  return pathPoints[0];
}

export function getRouteEndPoint(
  routePath: FeatureCollection
): [number, number] {
  const pathPoints = (routePath.features[0].geometry as any).coordinates as [
    number,
    number
  ][];
  return pathPoints[pathPoints.length - 1];
}

export function getSubRoute(
  routePath: FeatureCollection,
  point1: [number, number],
  point2: [number, number]
): turf.Feature<turf.LineString> | turf.LineString {
  const line = getRoute(routePath);
  const start = turf.point(point1);
  const stop = turf.point(point2);
  return turf.lineString(
    turf.lineSlice(start, stop, line).geometry.coordinates
  );
}

export function getRouteAfterPoint(
  routePath: FeatureCollection,
  point: [number, number]
): turf.Feature<turf.LineString> | turf.LineString {
  const endPoint = getRouteEndPoint(routePath);
  const line = getRoute(routePath);
  const start = turf.point(point);
  const stop = turf.point(endPoint);
  return turf.lineString(
    turf.lineSlice(start, stop, line).geometry.coordinates
  );
}

export function getRouteBeforePoint(
  routePath: FeatureCollection,
  point: [number, number]
): turf.Feature<turf.LineString> | turf.LineString {
  const startPoint = getRouteStartPoint(routePath);
  const line = getRoute(routePath);
  const start = turf.point(point);
  const stop = turf.point(startPoint);
  return turf.lineString(
    turf.lineSlice(start, stop, line).geometry.coordinates
  );
}

export function getDestination(
  startPoint: [number, number],
  distance: number,
  directionVector: [number, number]
): [number, number] {
  const calcAngle = (point: [number, number]): number => {
    return Math.atan2(point[0], point[1]) * (180 / Math.PI);
  };

  const destination = turf.destination(
    turf.point(startPoint),
    distance,
    calcAngle(directionVector)
  );
  return [
    destination.geometry.coordinates[0],
    destination.geometry.coordinates[1],
  ];
}

export function getDestinationFromAngle(
  startPoint: [number, number],
  distance: number,
  angle: number
): [number, number] {
  const destination = turf.destination(turf.point(startPoint), distance, angle);
  return [
    destination.geometry.coordinates[0],
    destination.geometry.coordinates[1],
  ];
}

export function getDistanceToRoute(
  routePath: FeatureCollection,
  point: [number, number]
): number {
  const line = getRoute(routePath);
  return turf.pointToLineDistance(turf.point(point), line);
}

export function getNearestPointOnRoute(
  routePath: FeatureCollection,
  point: [number, number]
): [number, number] {
  const line = getRoute(routePath);
  const snapped = turf.nearestPointOnLine(line, turf.point(point));
  return [snapped.geometry.coordinates[0], snapped.geometry.coordinates[1]];
}

export function isPointCloseToRoute(
  routePath: FeatureCollection,
  point: [number, number],
  searchDelta = 0.003
): boolean {
  return getDistanceToRoute(routePath, point) < searchDelta;
}

export function isCornerPointOnSegment(
  routePath: FeatureCollection,
  currentPoint: [number, number],
  targetPoint: [number, number],
  angleDelta = 10,
  maxSegment = 5
): { location: [number, number]; value: boolean } {
  const subRoute = getSubRoute(routePath, currentPoint, targetPoint) as any;
  const subCoordinates = subRoute.geometry.coordinates as [number, number][];
  if (subCoordinates.length < maxSegment + 1) {
    for (let i = subCoordinates.length - 2; i > 0; i--) {
      const routePoint = subCoordinates[i];
      if (
        turf.distance(currentPoint, routePoint) > 0 &&
        turf.distance(targetPoint, routePoint) > 0
      ) {
        const angle = getAngleDeviation(currentPoint, targetPoint, routePoint);
        if (angle < angleDelta) return { location: routePoint, value: true };
      }
    }
  }
  return { location: targetPoint, value: false };
}

export function isCornerBetweenPoints(
  routePath: FeatureCollection,
  currentPoint: [number, number],
  targetPoint: [number, number],
  angleDelta = 135,
  maxSegment = 5
): { location: [number, number]; value: boolean } {
  const subRoute = getSubRoute(routePath, currentPoint, targetPoint) as any;
  const subCoordinates = subRoute.geometry.coordinates as [number, number][];
  if (subCoordinates.length < maxSegment + 1) {
    for (let i = subCoordinates.length - 2; i > 0; i--) {
      const routePoint = subCoordinates[i];
      if (
        turf.distance(currentPoint, routePoint) > 0 &&
        turf.distance(targetPoint, routePoint) > 0
      ) {
        const angle = getAngleDeviation(routePoint, currentPoint, targetPoint);
        if (angle < angleDelta) {
          return { location: routePoint, value: true };
          console.log('isCornerBetweenPoints', angle);
        }
      }
    }
  }
  return { location: targetPoint, value: false };
}

export function moveAlongPath(
  routePath: FeatureCollection,
  actualPoint: [number, number],
  distance: number,
  direction: DrivingDirection = DrivingDirection.forward
): [number, number] {
  const drivenRoute =
    direction === DrivingDirection.forward
      ? getRouteAfterPoint(routePath, actualPoint)
      : getRouteBeforePoint(routePath, actualPoint);
  const pointOnDrivenLine = turf.along(drivenRoute, distance);
  return [
    pointOnDrivenLine.geometry.coordinates[0],
    pointOnDrivenLine.geometry.coordinates[1],
  ];
}

export function getRotation(
  actualPoint: [number, number],
  destinationPoint: [number, number]
): number {
  return turf.bearing(turf.point(actualPoint), turf.point(destinationPoint));
}

export function getAngleDeviation(
  center: [number, number],
  point1: [number, number],
  point2: [number, number]
): number {
  const angleToPoint1 = getRotation(center, point1);
  const angleToPoint2 = getRotation(center, point2);
  return Math.abs(angleToPoint1 - angleToPoint2);
}

export function getPathDeviation(
  path1: [[number, number], [number, number]],
  path2: [[number, number], [number, number]]
): number {
  const anglePath1 = getRotation(path1[0], path1[1]);
  const anglePath2 = getRotation(path2[0], path2[1]);
  const delta = Math.abs(anglePath1 - anglePath2);
  if (delta > 180) {
    const anglePath2 = getRotation(path2[1], path2[0]);
    return Math.abs(anglePath1 - anglePath2);
  }
  return delta;
}

export function isOnStart(
  routePath: FeatureCollection,
  activePoint: [number, number]
): boolean {
  const startPoint = getRouteStartPoint(routePath);
  const goalDistance = turf.distance(
    turf.point(activePoint),
    turf.point(startPoint)
  );
  return goalDistance < 0.001;
}

export function goalReached(
  routePath: FeatureCollection,
  activePoint: [number, number]
): boolean {
  const endPoint = getRouteEndPoint(routePath);
  const goalDistance = turf.distance(
    turf.point(activePoint),
    turf.point(endPoint)
  );
  return goalDistance < 0.001;
}
