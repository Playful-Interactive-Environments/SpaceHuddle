import { MglDefaults } from 'vue-maplibre-gl';
import mapStyleBasic from '@/assets/map-style/basic.json';
import mapStyleDark from '@/assets/map-style/dark.json';
import mapStyleLight from '@/assets/map-style/light.json';
import mapStyleTerrain from '@/assets/map-style/terrain.json';
import mapStyleStreets from '@/assets/map-style/streets.json';
import * as env from '@/utils/env';

/* eslint-disable @typescript-eslint/no-explicit-any*/
function updatePath(style: any, name: MapStyleType | null = null): any {
  style.glyphs = `${window.location.origin}/assets/fonts/{fontstack}/{range}.pbf`;
  if (name)
    style.sprite = `${window.location.origin}/assets/map-style/${name}/sprite`;
  let tileServer = env.getString('VUE_APP_TILE_SERVER');
  if (tileServer.includes('{key}')) {
    tileServer = tileServer.replace(
      '{key}',
      env.getString('VUE_APP_MAPTILER_KEY')
    );
  }
  style.sources.openmaptiles.url = tileServer;
  return style;
}

export enum MapStyleType {
  Basic = 'basic',
  Streets = 'streets',
  Dark = 'dark',
  Light = 'light',
  Terrain = 'terrain',
}

export function getMapStyle(name: MapStyleType): string {
  switch (name) {
    case MapStyleType.Basic:
      return updatePath({ ...mapStyleBasic }, name);
    case MapStyleType.Streets:
      return updatePath({ ...mapStyleStreets }, name);
    case MapStyleType.Dark:
      return updatePath({ ...mapStyleDark }, name);
    case MapStyleType.Light:
      return updatePath({ ...mapStyleLight }, name);
    case MapStyleType.Terrain:
      return updatePath({ ...mapStyleTerrain }, name);
    default:
      return updatePath({ ...mapStyleBasic }, name);
  }
}

export function setMapStyle(name: MapStyleType): void {
  MglDefaults.style = getMapStyle(name);
}

export function setMapStyleBasic(): void {
  MglDefaults.style = updatePath({ ...mapStyleBasic }, MapStyleType.Basic);
}

export function setMapStyleStreets(): void {
  MglDefaults.style = updatePath({ ...mapStyleStreets }, MapStyleType.Streets);
}

export function setMapStyleDark(): void {
  MglDefaults.style = updatePath({ ...mapStyleDark }, MapStyleType.Dark);
}

export function setMapStyleLight(): void {
  MglDefaults.style = updatePath({ ...mapStyleLight }, MapStyleType.Light);
}

export function setMapStyleTerrain(): void {
  MglDefaults.style = updatePath({ ...mapStyleTerrain }, MapStyleType.Terrain);
}
