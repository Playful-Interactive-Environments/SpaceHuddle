import { Map, LayerSpecification, MapGeoJSONFeature } from 'maplibre-gl';
import { createCanvas } from 'canvas';
import * as turfUtils from '@/utils/turf';

/* eslint-disable @typescript-eslint/no-explicit-any*/

export function connectIfFits(
  street01: [number, number][],
  street02: [number, number][]
): [number, number][] {
  if (
    street01[street01.length - 1][0] === street02[0][0] &&
    street01[street01.length - 1][1] === street02[0][1] &&
    street01[street01.length - 2][0] !== street02[1][0] &&
    street01[street01.length - 2][1] !== street02[1][1]
  ) {
    return [...street01.slice(0, -1), ...street02];
  }
  return [];
}

export function connectIfOverlaps(
  street01: [number, number][],
  street02: [number, number][]
): [number, number][] {
  for (let i = street01.length - 1; i > 0; i--) {
    const checkCount = street01.length - i;
    if (street02.length < checkCount) return [];
    let match = true;
    for (let j = i, k = 0; j < street01.length; j++, k++) {
      if (
        street02.length <= k ||
        street01[j][0] !== street02[k][0] ||
        street01[j][1] !== street02[k][1]
      ) {
        match = false;
        break;
      }
    }
    if (match) {
      const j = street01.length - 1 - i;
      if (
        i === street01.length - 1 &&
        street01[i - 1][0] === street02[1][0] &&
        street01[i - 1][1] === street02[1][1]
      )
        return [];
      if (
        street02.length > j + 1 &&
        street01[i - 1][0] === street02[j + 1][0] &&
        street01[i - 1][1] === street02[j + 1][1]
      )
        return [];
      return [...street01.slice(0, i), ...street02];
    }
  }
  return [];
}

export function checkTotalOverlay(
  street01: [number, number][],
  street02: [number, number][]
): boolean {
  for (let i = street01.length - street02.length; i >= 0; i--) {
    let match = true;
    for (let j = i, k = 0; k < street02.length; j++, k++) {
      if (
        street01[j][0] !== street02[k][0] ||
        street01[j][1] !== street02[k][1]
      ) {
        match = false;
        break;
      }
    }
    if (match) return true;
  }
  return false;
}

export function checkTotalOverlayingRoads(road01: Road, road02: Road): boolean {
  for (let i = road01.parts.length - road02.parts.length; i >= 0; i--) {
    let match = true;
    for (let j = i, k = 0; k < road02.parts.length; j++, k++) {
      if (road01.parts[j] !== road02.parts[k]) {
        match = false;
        break;
      }
    }
    if (match) return true;
  }
  return false;
}

export function removeTotalOverlay(
  roads: [number, number][][]
): [number, number][][] {
  const streets: [number, number][][] = [];
  for (let i = 0; i < roads.length; i++) {
    const street01 = roads[i];
    let overlay = false;
    for (let j = 0; j < roads.length; j++) {
      if (i === j) continue;
      const street02 = roads[j];
      const sameLength = street01.length === street02.length;
      overlay = checkTotalOverlay(street02, street01);
      if (overlay) {
        if (!sameLength || i > j) break;
        else overlay = false;
      }
      overlay = checkTotalOverlay(street02, [...street01].reverse());
      if (overlay) {
        if (!sameLength || i > j) break;
        else overlay = false;
      }
    }
    if (!overlay) streets.push(street01);
  }
  return streets;
}

export function removeTotalOverlayingRoads(roads: Road[]): Road[] {
  const streets: Road[] = [];
  for (let i = 0; i < roads.length; i++) {
    const road01 = roads[i];
    let overlay = false;
    for (let j = 0; j < roads.length; j++) {
      if (i === j) continue;
      const road02 = roads[j];
      const sameLength = road01.parts.length === road02.parts.length;
      overlay = checkTotalOverlayingRoads(road02, road01);
      if (overlay) {
        if (!sameLength || i > j) break;
        else overlay = false;
      }
      overlay = checkTotalOverlayingRoads(road02, reversRoad(road01));
      if (overlay) {
        if (!sameLength || i > j) break;
        else overlay = false;
      }
    }
    if (!overlay) streets.push(road01);
  }
  return streets;
}

export interface Road {
  coordinates: [number, number][];
  parts: number[];
}

export function convertToRoad(street: [number, number][], index: number): Road {
  return {
    coordinates: street,
    parts: [index],
  };
}

export function convertToRoadList(streets: [number, number][][]): Road[] {
  return streets.map((street, index) => convertToRoad(street, index));
}

export function reversRoad(road: Road): Road {
  return {
    coordinates: [...road.coordinates].reverse(),
    parts: [...road.parts].reverse(),
  };
}

export function connectStreets(
  roads: Road[],
  baseRoads: Road[] | null = null,
  loopCount = 0
): Road[] {
  roads = removeTotalOverlayingRoads(roads);
  const roadList = roads.map((road) => {
    return {
      road: road,
      findConnection: false,
    };
  });
  const streets: Road[] = [];
  let findConnection = false;
  for (let i = 0; i < roadList.length; i++) {
    const road01 = roadList[i].road;
    if (!baseRoads) {
      for (let j = i + 1; j < roadList.length; j++) {
        const road02 = roadList[j].road;
        const tryConnection = (road01: Road, road02: Road): boolean => {
          const street = connectIfFits(road01.coordinates, road02.coordinates);
          if (street.length > 0) {
            findConnection = true;
            roadList[i].findConnection = true;
            roadList[j].findConnection = true;
            streets.push({
              coordinates: street,
              parts: [...road01.parts, ...road02.parts],
            });
            return true;
          }
          return false;
        };
        if (!tryConnection(road01, road02))
          if (!tryConnection(reversRoad(road01), road02))
            if (!tryConnection(road01, reversRoad(road02)))
              tryConnection(reversRoad(road01), reversRoad(road02));
      }
    } else {
      for (let j = 0; j < baseRoads.length; j++) {
        if (road01.parts.includes(j)) continue;
        const road02 = baseRoads[j];
        //if (checkTotalOverlayingRoads(road01, road02)) continue;
        //if (checkTotalOverlayingRoads(road01, reversRoad(road02))) continue;
        const tryConnection = (road01: Road, road02: Road): boolean => {
          const street = connectIfFits(road01.coordinates, road02.coordinates);
          if (street.length > 0) {
            findConnection = true;
            roadList[i].findConnection = true;
            streets.push({
              coordinates: street,
              parts: [...road01.parts, ...road02.parts],
            });
            return true;
          }
          return false;
        };
        if (!tryConnection(road01, road02))
          if (!tryConnection(reversRoad(road01), road02))
            if (!tryConnection(road01, reversRoad(road02)))
              tryConnection(reversRoad(road01), reversRoad(road02));
      }
    }
    if (!roadList[i].findConnection) {
      streets.push(road01);
    }
  }
  if (findConnection && loopCount < 6) {
    return connectStreets(streets, baseRoads ?? roads, loopCount + 1);
  }
  return streets;
}

export const testRoads: [number, number][][] = [
  [
    [14.285917282104492, 48.30691550235801],
    [14.286566376686096, 48.30715813191841],
    [14.286877512931824, 48.30728658239519],
  ],
  [
    [14.285863637924194, 48.306983296027795],
    [14.285670518875122, 48.30725090173962],
  ],
  [
    [14.285917282104492, 48.30691550235801],
    [14.285863637924194, 48.306983296027795],
  ],
  [
    [14.285697340965271, 48.30692977471702],
    [14.285756349563599, 48.30685127669307],
  ],
  [
    [14.285504221916199, 48.30719738070954],
    [14.285697340965271, 48.30692977471702],
  ],
  [
    [14.285756349563599, 48.30685127669307],
    [14.285917282104492, 48.30691550235801],
  ],
  [
    [14.286502003669739, 48.30613051646574],
    [14.28646981716156, 48.306205447458495],
    [14.286324977874756, 48.30640883102723],
    [14.286180138587952, 48.30658010077221],
    [14.286110401153564, 48.30665146299637],
    [14.285917282104492, 48.30691550235801],
  ],
  [
    [14.285756349563599, 48.30685127669307],
    [14.285799264907837, 48.30679418714524],
  ],
  [
    [14.285799264907837, 48.30679418714524],
    [14.285944104194641, 48.30660150944993],
    [14.28596556186676, 48.30656226020062],
  ],
  [
    [14.28596556186676, 48.30656226020062],
    [14.286169409751892, 48.30619831117821],
    [14.286190867424011, 48.30610910759043],
  ],
  [
    [14.285756349563599, 48.30685127669307],
    [14.285686612129211, 48.30682630002377],
  ],
];

export function connectionTestResult(): Road[] {
  return connectStreets(convertToRoadList(testRoads));
}

export function getNotNeededLayers(map: Map): LayerSpecification[] {
  return map.getStyle().layers.filter((layer) => {
    const layerCategory = layer['source-layer'];
    const layerType = layer['type'];
    if (layerCategory) {
      return layerType === 'symbol' && layerCategory !== 'place';
    }
    return false;
  });
}

export function getStreetLayers(map: Map): string[] {
  const notNeededLayers = getNotNeededLayers(map);

  return map
    .getStyle()
    .layers.filter((layer) => {
      return (
        (layer.id.includes('highway') ||
          //layer.id.includes('primary') ||
          layer.id.includes('bridge') ||
          layer.id.includes('tunnel')) &&
        !layer.id.includes('casing') &&
        !layer.id.includes('path') &&
        !layer.id.includes('area') &&
        !layer.id.includes('waterway') &&
        !layer.id.includes('railway') &&
        layer.type === 'line' &&
        !notNeededLayers.includes(layer)
      );
    })
    .map((layer) => layer.id)
    .filter((value, index, array) => array.indexOf(value) === index);
}

export function getStreetFeaturesInRegion(
  map: Map,
  streetLayers: string[],
  pixelPos01: [number, number] = [0, 0],
  pixelPos02: [number, number] = [100, 100],
  pixelDelta = 0
): MapGeoJSONFeature[] {
  if (map) {
    return (
      map
        .queryRenderedFeatures(
          [
            [pixelPos01[0] - pixelDelta, pixelPos01[1] - pixelDelta],
            [pixelPos02[0] + pixelDelta, pixelPos02[1] + pixelDelta],
          ],
          { layers: streetLayers }
        )
        //.filter((f) => f.properties.class !== 'service')
        .filter((f) => f.properties.surface !== 'unpaved')
        .filter(
          (f) =>
            f.properties.subclass !== 'pedestrian' &&
            f.properties.subclass !== 'footway' &&
            f.properties.subclass !== 'cycleway'
        )
    );
  }
  return [];
}

export function calculateStreetMask(map: Map, streetLayers: string[]): string {
  if (map) {
    const mapCanvas = map.getCanvas();
    const bounds = map.getBounds();
    const delta = 0;
    const streets = getStreetFeaturesInRegion(
      map,
      streetLayers,
      [0, 0],
      [mapCanvas.width, mapCanvas.height],
      delta
    );

    const canvas = createCanvas(mapCanvas.width, mapCanvas.height);
    const ctx = canvas.getContext('2d');
    if (!ctx) return '';
    ctx.strokeStyle = 'black';
    ctx.lineWidth = 10;
    for (const street of streets) {
      const coordinates: [number, number][] = (street.geometry as any)
        .coordinates;
      if (coordinates.length >= 2) {
        ctx.beginPath();
        const lngLatStart: [number, number] = coordinates[0];
        if (Array.isArray(lngLatStart)) {
          const start = turfUtils.lngLatToPixel(
            lngLatStart,
            bounds,
            mapCanvas.width,
            mapCanvas.height
          );
          ctx.moveTo(start[0], start[1]);
          for (let i = 1; i < coordinates.length; i++) {
            const lngLatEnd: [number, number] = coordinates[i];
            if (Array.isArray(lngLatStart) && Array.isArray(lngLatEnd)) {
              const end = turfUtils.lngLatToPixel(
                lngLatEnd,
                bounds,
                mapCanvas.width,
                mapCanvas.height
              );
              ctx.lineTo(end[0], end[1]);
            }
          }
          ctx.stroke();
        }
      }
    }
    return canvas.toDataURL();
  }
  return '';
}

export function getStreetsInRegion(
  map: Map,
  streetLayers: string[],
  pixelPos01: [number, number] = [0, 0],
  pixelPos02: [number, number] = [100, 100],
  pixelDelta = 0
): [number, number][][] {
  /*return connectStreets(testRoads);*/

  if (map) {
    const roadList = getStreetFeaturesInRegion(
      map,
      streetLayers,
      pixelPos01,
      pixelPos02,
      pixelDelta
    ).map((road) => (road.geometry as any).coordinates as [number, number][]);
    return connectStreets(convertToRoadList(roadList)).map(
      (road) => road.coordinates
    );
  }
  return [];
}
