import { MglDefaults } from 'vue-maplibre-gl';
import * as mapStyleBasic from '@/assets/map-style/basic.json';
import * as mapStyleDark from '@/assets/map-style/dark.json';
import * as mapStyleLight from '@/assets/map-style/light.json';
import * as mapStyleTerrain from '@/assets/map-style/terrain.json';
import * as mapStyleStreets from '@/assets/map-style/streets.json';

/* eslint-disable @typescript-eslint/no-explicit-any*/
function updatePath(style: any): any {
  style.glyphs = `${window.location.origin}/assets/fonts/{fontstack}/{range}.pbf`;
  let tileServer = process.env.VUE_APP_TILE_SERVER;
  if (tileServer.includes('{key}')) {
    tileServer = tileServer.replace('{key}', process.env.VUE_APP_MAPTILER_KEY);
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
      return updatePath({ ...mapStyleBasic });
    case MapStyleType.Streets:
      return updatePath({ ...mapStyleStreets });
    case MapStyleType.Dark:
      return updatePath({ ...mapStyleDark });
    case MapStyleType.Light:
      return updatePath({ ...mapStyleLight });
    case MapStyleType.Terrain:
      return updatePath({ ...mapStyleTerrain });
    default:
      return updatePath({ ...mapStyleBasic });
  }
}

export function setMapStyle(name: MapStyleType): void {
  MglDefaults.style = getMapStyle(name);
}

export function setMapStyleBasic(): void {
  MglDefaults.style = updatePath({ ...mapStyleBasic });
}

export function setMapStyleStreets(): void {
  MglDefaults.style = updatePath({ ...mapStyleStreets });
}

export function setMapStyleDark(): void {
  MglDefaults.style = updatePath({ ...mapStyleDark });
}

export function setMapStyleLight(): void {
  MglDefaults.style = updatePath({ ...mapStyleLight });
}

export function setMapStyleTerrain(): void {
  MglDefaults.style = updatePath({ ...mapStyleTerrain });

  /*const style = { ...mapStyleTerrain };

  style.glyphs = `${window.location.origin}${style.glyphs}`;
  style.sources.openmaptiles.url = process.env.VUE_APP_TILE_SERVER;
  style.payload.blob.rawBlob.glyphs = `${window.location.origin}${style.payload.blob.rawBlob.glyphs}`;
  style.payload.blob.rawBlob.sources.openmaptiles.url =
    process.env.VUE_APP_TILE_SERVER;
  MglDefaults.style = style;*/
}
