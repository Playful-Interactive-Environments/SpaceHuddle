import * as PIXI from 'pixi.js';
import { v4 as uuidv4 } from 'uuid';
import { IGameObjectSource } from '@/components/shared/atoms/game/GameObject.vue';

export enum BoundingBoxShape {
  rect = 'rect',
  circle = 'circle',
  polygon = 'polygon',
}

/* eslint-disable @typescript-eslint/no-explicit-any*/
export interface PlaceableBase {
  type: string;
  name: string;
  position: [number, number];
  rotation: number;
  scale: number;
  saturation: number;
}

export interface Placeable extends PlaceableBase {
  uuid: string;
  pivot: [number, number];
  texture: string | PIXI.Texture;
  width: number;
  shape: BoundingBoxShape;
  polygonShape: [number, number][] | undefined;
  placingRegions: [number, number][][] | undefined;
}

export interface PlaceableItemConfig {
  maxCount: number;
  width: number;
  shape: BoundingBoxShape;
  polygonShape: [number, number][] | undefined;
  pivot: [number, number] | undefined;
  placingRegions: [number, number][][] | undefined;
}

export interface PlaceableCategoryConfig {
  settings: {
    icon: string;
    order: number;
    spritesheet: string;
    placingRegions: [number, number][][] | undefined;
    checkCompletelyInside: boolean | undefined;
  };
  items: {
    [name: string]: PlaceableItemConfig;
  };
}

export interface PlaceableThemeConfig {
  settings: {
    background: string;
    defaultType: string;
    defaultName: string;
    icon: string;
    color: string;
  };
  categories: {
    [category: string]: PlaceableCategoryConfig;
  };
}

export interface PlaceableConfig {
  [theme: string]: PlaceableThemeConfig;
}

export function convertToBase(placeable: Placeable): PlaceableBase {
  return {
    type: placeable.type,
    name: placeable.name,
    position: [...placeable.position],
    rotation: placeable.rotation,
    scale: placeable.scale,
    saturation: placeable.saturation,
  };
}

export function getConfigParameter(
  value: PlaceableBase,
  categoryConfig: PlaceableThemeConfig
): PlaceableItemConfig {
  if (!value.type) value.type = categoryConfig.settings.defaultType;
  return categoryConfig.categories[value.type].items[value.name];
}

export function convertToDetailData(
  value: PlaceableBase,
  categoryConfig: PlaceableThemeConfig,
  texture: string | PIXI.Texture
): Placeable {
  const configParameter = getConfigParameter(value, categoryConfig);
  if (!configParameter) {
    return {
      uuid: uuidv4(),
      type: value.type,
      name: value.name,
      texture: texture,
      width: 1,
      shape: BoundingBoxShape.rect,
      polygonShape: undefined,
      pivot: [0.5, 0.5],
      position: value.position,
      rotation: value.rotation,
      scale: value.scale,
      placingRegions: undefined,
      saturation: 1,
    };
  }
  return {
    uuid: uuidv4(),
    type: value.type,
    name: value.name,
    texture: texture,
    width: configParameter.width,
    shape: configParameter.shape,
    polygonShape: configParameter.polygonShape,
    pivot: configParameter.pivot ?? [0.5, 0.5],
    position: value.position,
    rotation: value.rotation,
    scale: value.scale,
    placingRegions: configParameter.placingRegions,
    saturation: value.saturation ?? 1,
  };
}

export function convertToGameSourceData(
  value: PlaceableBase,
  categoryConfig: PlaceableThemeConfig,
  texture: string | PIXI.Texture
): Placeable & IGameObjectSource {
  const item = convertToDetailData(value, categoryConfig, texture) as any;
  item.gameObject = null;
  return item;
}
