import { FeatureCollection } from 'geojson';
import * as turf from '@turf/turf';
import * as turfUtils from '@/utils/turf';
import { OsrmCustom, OSRMWayPoint } from '@/utils/osrm';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export const osrmProfile = 'car';
export async function calculateRoute(
  drivenPath: FeatureCollection,
  start: [number, number],
  end: [number, number],
  checkDistance = false,
  intermediateGoals: [number, number][] = []
): Promise<FeatureCollection | null> {
  const osrm = new OsrmCustom(osrmProfile, {
    userAgent: '',
  });
  const drivenPathCoordinates = (drivenPath.features[0].geometry as any)
    .coordinates;
  const wayPoints: [number, number][] = [];
  if (drivenPathCoordinates.length > 0) {
    const last = drivenPathCoordinates[drivenPathCoordinates.length - 1];
    wayPoints.push([last[1], last[0]]);
  }
  wayPoints.push([start[1], start[0]]);
  wayPoints.push(
    ...intermediateGoals.map((p) => [p[1], p[0]] as [number, number])
  );
  wayPoints.push([end[1], end[0]]);
  const path = await osrm.directions(wayPoints, osrmProfile);
  const pathCoordinates: [number, number][] = [];
  path.directions.forEach((direction) => {
    if (direction.feature.geometry) {
      pathCoordinates.push(
        ...(direction.feature.geometry.coordinates.map((position) => [
          position[1],
          position[0],
        ]) as [number, number][])
      );
    }
  });
  let setNewPath = true;
  if (checkDistance) {
    const line = turf.lineString(pathCoordinates);
    const pt = turf.point(start);
    const distance = turf.pointToLineDistance(pt, line);
    setNewPath = distance < 0.01;
  }
  if (setNewPath) {
    return turfUtils.getRouteObject(pathCoordinates);
  }
  return null;
}

/*
  searchPoints: [number, number][] = [];
  export const searchPointColors = [
    'red',
    'green',
    'blue',
    'yellow',
    'magenta',
    'orange',
    'gray',
    'white',
    'black',
    'cyan',
    'violet',
  ];
 */
export async function isOnPossibleRoute(
  initialPoint: [number, number],
  searchPoint: [number, number],
  routePath: FeatureCollection
): Promise<{
  distance: number;
  location: [number, number];
  delta: number;
  pathDelta: number;
  isOnRoutePath: boolean;
}> {
  const getPoint = (path: OSRMWayPoint): [number, number] => {
    return [path.location[0], path.location[1]];
  };
  //eslint-disable-next-line @typescript-eslint/no-unused-vars
  const checkWay = (waypoints: OSRMWayPoint[], name: string): void => {
    let minDelta = pathDelta;
    let minPoint = waypoints[0];
    for (let i = 1; i < waypoints.length; i++) {
      const waypointDelta = turfUtils.getPathDeviation(
        [initialPoint, searchPoint],
        [getPoint(waypoints[i - 1]), getPoint(waypoints[i])]
      );
      if (waypointDelta < pathDelta) {
        minDelta = waypointDelta;
      }
      if (
        (minPoint.distance as number) > (waypoints[i - 1].distance as number)
      ) {
        minPoint = waypoints[i - 1];
      }
    }
    if (minDelta < pathDelta) {
      pathDelta = minDelta;
      path = minPoint;
    }
  };
  const osrm = new OsrmCustom(osrmProfile, {
    userAgent: '',
  });
  //this.checkRoutePoint = searchPoint;
  const nearest = await osrm.nearest(
    [searchPoint[1], searchPoint[0]],
    osrmProfile,
    10
  );
  let path = nearest.waypoints[0];
  const searchPointList = nearest.waypoints.map((wp) => getPoint(wp));
  let pathDelta = 180;
  if (searchPointList.length > 1) {
    pathDelta = turfUtils.getPathDeviation(
      [initialPoint, searchPoint],
      [searchPointList[0], searchPointList[1]]
    );
    const streets = [
      ...new Set(nearest.waypoints.map((wp) => wp.name)),
    ] as string[];
    for (const street of streets) {
      const waypoints = nearest.waypoints.filter((wp) => wp.name === street);
      const hints = [
        ...new Set(waypoints.map((wp) => wp.hint.substring(0, 10))),
      ] as string[];
      checkWay(waypoints, street);
      for (const hint of hints) {
        checkWay(
          waypoints.filter((wp) => wp.hint.startsWith(hint)),
          hint
        );
      }
    }
  }
  const location = getPoint(path);
  const delta = turfUtils.getAngleDeviation(
    initialPoint,
    searchPoint,
    location
  );
  return {
    distance: path.distance,
    location: location,
    delta: delta,
    pathDelta: pathDelta,
    isOnRoutePath: turfUtils.isPointOnPath(routePath, location),
  };
}
