import { FeatureCollection } from 'geojson';
import * as turf from '@turf/turf';

export enum DrivingDirection {
  forward,
  backward,
}

/* eslint-disable @typescript-eslint/no-explicit-any*/
export function getRoute(
  routePath: FeatureCollection | turf.Feature<turf.LineString>
): turf.Feature<turf.LineString> {
  if ('features' in routePath) {
    const pathPoints = (routePath.features[0].geometry as any).coordinates as [
      number,
      number
    ][];
    return turf.lineString(pathPoints);
  }
  return routePath;
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

export function distancePointToStart(
  routePath: FeatureCollection,
  point: [number, number]
): number[] {
  let startDistanceList: {
    startDist: number;
    pointDistanceToSegment: number;
  }[] = [];
  const line = getRoute(routePath);
  const segments = turf.lineSegment(line);
  const distanceDelta = 0.005;
  let pastLineDistance = 0;
  for (const segment of segments.features) {
    const segmentLength = turf.length(segment);
    const nearestPoint = turf.nearestPointOnLine(segment, point);
    const pointDistanceToSegment = turf.distance(nearestPoint, point);
    if (pointDistanceToSegment < distanceDelta) {
      const startDist =
        pastLineDistance +
        turf.distance(segment.geometry.coordinates[0], nearestPoint);
      const value = {
        startDist: startDist,
        pointDistanceToSegment: pointDistanceToSegment,
      };
      if (startDistanceList.length > 0) {
        const previous = startDistanceList[startDistanceList.length - 1];
        if (Math.abs(previous.startDist - startDist) > distanceDelta) {
          startDistanceList.push(value);
        } else if (previous.pointDistanceToSegment > pointDistanceToSegment) {
          previous.startDist = startDist;
          previous.pointDistanceToSegment = pointDistanceToSegment;
        }
      } else startDistanceList.push(value);
    }
    pastLineDistance += segmentLength;
  }
  startDistanceList = startDistanceList.sort(
    (a, b) => a.pointDistanceToSegment - b.pointDistanceToSegment
  );
  return startDistanceList.map((entry) => entry.startDist);
}

export function getSubRoute(
  routePath: FeatureCollection | turf.Feature<turf.LineString>,
  point1: [number, number],
  point2: [number, number]
): turf.Feature<turf.LineString> {
  const line = getRoute(routePath);
  const start = turf.point(point1);
  const stop = turf.point(point2);
  return turf.lineString(
    turf.lineSlice(start, stop, line).geometry.coordinates
  );
}

export function getSubRouteFromDist(
  routePath: FeatureCollection | turf.Feature<turf.LineString>,
  startDist: number,
  endDist: number
): turf.Feature<turf.LineString> | null {
  const pathLength = turf.length(routePath);
  if (startDist < 0) startDist = 0;
  if (endDist > pathLength) endDist = pathLength;
  if (startDist === endDist) {
    return null;
  }
  const line = getRoute(routePath);
  return turf.lineString(
    turf.lineSliceAlong(line, startDist, endDist).geometry.coordinates
  );
}

export function getRouteAfterPoint(
  routePath: FeatureCollection,
  point: [number, number]
): turf.Feature<turf.LineString> {
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
): turf.Feature<turf.LineString> {
  const startPoint = getRouteStartPoint(routePath);
  const line = getRoute(routePath);
  const start = turf.point(startPoint);
  const stop = turf.point(point);
  return turf.lineString(
    [...turf.lineSlice(start, stop, line).geometry.coordinates].reverse()
  );
}

export function isPointOnPath(
  routePath: FeatureCollection,
  point: [number, number]
): boolean {
  const minDistance = 0.001;
  const line = getRoute(routePath);
  const nearest = turf.nearestPointOnLine(line, point);
  const distance = turf.distance(nearest, point);
  return distance < minDistance;
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

export function isCornerAlongSegment(
  segment: [number, number][],
  angleDelta = 145,
  maxSegment = 10
): { location: [number, number]; value: boolean } {
  if (segment.length < maxSegment + 1) {
    for (let i = 1; i < segment.length - 1; i++) {
      const routePoint1 = segment[i - 1];
      const routePoint2 = segment[i];
      const routePoint3 = segment[i + 1];
      if (
        turf.distance(routePoint1, routePoint2) > 0 &&
        turf.distance(routePoint2, routePoint3) > 0
      ) {
        const angle = getAngleDeviation(routePoint2, routePoint1, routePoint3);
        if (angle < angleDelta) {
          return { location: routePoint2, value: true };
        }
      }
    }
  }
  return { location: segment[0], value: false };
}

export function isCornerBetweenPoints(
  routePath: FeatureCollection,
  currentPoint: [number, number],
  targetPoint: [number, number],
  angleDelta = 110,
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
): { location: [number, number]; distanceFromStart: number } {
  const drivenRoute =
    direction === DrivingDirection.forward
      ? getRouteAfterPoint(routePath, actualPoint)
      : getRouteBeforePoint(routePath, actualPoint);
  const distanceFromStart = turf.length(
    getRouteBeforePoint(routePath, actualPoint)
  );
  const pointOnDrivenLine = turf.along(drivenRoute, distance);
  return {
    location: [
      pointOnDrivenLine.geometry.coordinates[0],
      pointOnDrivenLine.geometry.coordinates[1],
    ],
    distanceFromStart: distanceFromStart,
  };
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
  const deviation = Math.abs(angleToPoint1 - angleToPoint2);
  if (deviation < 180) return deviation;
  return 360 - deviation;
}

export function getMinMaxAngleForPathSegment(
  routePath: FeatureCollection,
  actualPoint: [number, number],
  endPoint: [number, number],
  actualPointDist: number,
  speedDrivingDistance: number,
  buffer = 0,
  reversePath = false
): {
  min: number;
  max: number;
  endPoint: [number, number];
  corner: [number, number] | null;
  subPath: [number, number][];
} {
  const convertCoordinates = (
    subCoordinates: [number, number][]
  ): [number, number][] => {
    removeSameNeighbors(subCoordinates);
    return reversePath ? [...subCoordinates].reverse() : subCoordinates;
  };
  const returnEndPoint = reversePath ? actualPoint : endPoint;
  const returnStartPoint = reversePath ? endPoint : actualPoint;
  const defaultReturn = {
    min: 0,
    max: 360,
    endPoint: returnEndPoint,
    corner: null as [number, number] | null,
    subPath: [returnStartPoint, returnEndPoint],
  };
  const removeSameNeighbors = (list: [number, number][]) => {
    for (let i = list.length - 1; i > 1; i--) {
      const distance = turf.distance(list[i], list[i - 1]);
      if (distance < 0.00001) {
        list.splice(i, 1);
      }
    }
    return list;
  };
  const pathSegment = reversePath
    ? getSubRouteFromDist(
        routePath,
        actualPointDist - speedDrivingDistance,
        actualPointDist
      )
    : getSubRouteFromDist(
        routePath,
        actualPointDist,
        actualPointDist + speedDrivingDistance
      );
  if (!pathSegment) return defaultReturn;
  let subRoute = pathSegment;
  let subCoordinates = subRoute.geometry.coordinates as [number, number][];
  let convertedCoordinates = convertCoordinates(subCoordinates);
  if (convertedCoordinates.length <= 1) {
    return defaultReturn;
  }
  const corner = isCornerAlongSegment(convertedCoordinates);
  defaultReturn.corner = corner.value ? corner.location : null;
  if (corner.value) {
    const cornerDistance = reversePath
      ? turf.distance(corner.location, endPoint)
      : turf.distance(corner.location, actualPoint);
    if (cornerDistance > 0.001) {
      if (reversePath)
        subRoute = getSubRoute(subRoute, corner.location, endPoint) as any;
      else
        subRoute = getSubRoute(subRoute, actualPoint, corner.location) as any;
      subCoordinates = subRoute.geometry.coordinates as [number, number][];
      convertedCoordinates = convertCoordinates(subCoordinates);
    } else {
      corner.value = false;
    }
  }
  if (convertedCoordinates.length <= 1) {
    return defaultReturn;
  }
  let min180 = turf.bearing(convertedCoordinates[0], convertedCoordinates[1]);
  let max180 = min180;
  let min360 = min180 > 0 ? min180 : 360 + min180;
  let max360 = min360;
  let segmentAngle: number[] = [];
  if (min180 !== 0) segmentAngle.push(min180);

  for (let i = 2; i < convertedCoordinates.length; i++) {
    const angle180 = turf.bearing(
      convertedCoordinates[i - 1],
      convertedCoordinates[i]
    );
    if (angle180 === 0) continue;
    const angle360 = angle180 > 0 ? angle180 : 360 + angle180;
    segmentAngle.push(angle180);
    if (min180 === 0 || angle180 < min180) min180 = angle180;
    if (max180 === 0 || angle180 > max180) max180 = angle180;
    if (min360 === 0 || angle360 < min360) min360 = angle360;
    if (max360 === 0 || angle360 > max360) max360 = angle360;
  }
  let min = min180;
  let max = max180;
  if (max180 - min180 > max360 - min360) {
    min = min360;
    max = max360;
    segmentAngle = segmentAngle.map((angle) =>
      angle > 0 ? angle : 360 + angle
    );
  }

  for (let i = 0; i < segmentAngle.length; i++) {
    if (segmentAngle[0] < min || segmentAngle[0] > max) {
      return {
        min: max - buffer,
        max: min + buffer + 360,
        endPoint: corner.value ? corner.location : returnEndPoint,
        corner: corner.value ? corner.location : null,
        subPath: convertedCoordinates,
      };
    }
  }
  return {
    min: min - buffer,
    max: max + buffer,
    endPoint: corner.value ? corner.location : returnEndPoint,
    corner: corner.value ? corner.location : null,
    subPath: convertedCoordinates,
  };
}

export function getMinMaxAngleForPathDistanceSegment(
  routePath: FeatureCollection,
  actualPoint: [number, number],
  airlinePoint: [number, number],
  distance: number,
  buffer = 0
): {
  min: number;
  max: number;
  endPoint: [number, number];
  corner: [number, number] | null;
  subPath: [number, number][];
  checkPoints: [number, number][];
} {
  const checkPointDistance = (
    checkPoint: [number, number],
    checkDirection: DrivingDirection,
    checkStartDist: number
  ): void => {
    const pointDistance = turf.distance(checkPoint, airlinePoint);
    if (pointDistance < roadDistance) {
      roadDistance = pointDistance;
      endPoint = checkPoint;
      direction = checkDirection;
      startDistance = checkStartDist;
      findPoint = true;
    }
  };
  const line = getRoute(routePath);
  const lineLength = turf.length(line);
  const pointDistanceList = distancePointToStart(routePath, actualPoint);
  let findPoint = false;
  let endPoint = airlinePoint;
  let roadDistance = lineLength * 1000;
  let direction: DrivingDirection = DrivingDirection.forward;
  let startDistance = 0;
  for (const pointDistance of pointDistanceList) {
    if (pointDistance + distance < lineLength) {
      const endPointForward = turf.along(line, pointDistance + distance);
      checkPointDistance(
        endPointForward.geometry.coordinates as [number, number],
        DrivingDirection.forward,
        pointDistance
      );
    } else {
      const endPointForward = getRouteEndPoint(routePath);
      checkPointDistance(
        endPointForward,
        DrivingDirection.forward,
        pointDistance
      );
    }
    if (pointDistance - distance > 0) {
      const endPointBackward = turf.along(line, pointDistance - distance);
      checkPointDistance(
        endPointBackward.geometry.coordinates as [number, number],
        DrivingDirection.backward,
        pointDistance
      );
    } else {
      const endPointBackward = getRouteStartPoint(routePath);
      checkPointDistance(
        endPointBackward,
        DrivingDirection.backward,
        pointDistance
      );
    }
  }

  let result: any = {
    min: 0,
    max: 360,
    endPoint: airlinePoint,
    corner: null,
    subPath: [],
    checkPoints: [],
  };

  if (findPoint) {
    if (direction === DrivingDirection.forward) {
      result = getMinMaxAngleForPathSegment(
        routePath,
        actualPoint,
        endPoint,
        startDistance,
        distance,
        buffer
      );
    } else {
      result = getMinMaxAngleForPathSegment(
        routePath,
        endPoint,
        actualPoint,
        startDistance,
        distance,
        buffer,
        true
      );
    }
  }
  result.checkPoints = pointDistanceList.map(
    (d) => turf.along(line, d).geometry.coordinates as [number, number]
  );
  result.checkPoints.push(endPoint);
  result.checkPoints.push(actualPoint);
  return result;
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

export function distanceToGoal(
  routePath: FeatureCollection,
  activePoint: [number, number]
): number {
  const endPoint = getRouteEndPoint(routePath);
  return turf.distance(turf.point(activePoint), turf.point(endPoint));
}

export function goalReached(
  routePath: FeatureCollection,
  activePoint: [number, number]
): boolean {
  return distanceToGoal(routePath, activePoint) < 0.001;
}
