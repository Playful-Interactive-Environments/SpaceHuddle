import * as PIXI from 'pixi.js';
import { v4 as uuidv4 } from 'uuid';

/* eslint-disable @typescript-eslint/no-explicit-any*/
export interface PlaceableBase {
  type: string;
  name: string;
  position: [number, number];
  rotation: number;
  scale: number;
}

export interface Placeable extends PlaceableBase {
  uuid: string;
  id: number;
  texture: string | PIXI.Texture;
  width: number;
  shape: 'rect' | 'circle';
  escalationSteps: number[];
  escalationStepIndex: number;
}

export function convertToBase(placeable: Placeable): PlaceableBase {
  return {
    type: placeable.type,
    name: placeable.name,
    position: placeable.position,
    rotation: placeable.rotation,
    scale: placeable.scale,
  };
}

export function convertToDetailData(
  value: PlaceableBase,
  gameConfig: any,
  texture: string | PIXI.Texture
): Placeable {
  if (!value.type) value.type = gameConfig.settings.defaultType;
  const configParameter = gameConfig[value.type][value.name];
  if (!configParameter) {
    return {
      uuid: uuidv4(),
      id: 0,
      type: value.type,
      name: value.name,
      texture: texture,
      width: 1,
      shape: 'rect',
      position: value.position,
      rotation: value.rotation,
      scale: value.scale,
      escalationSteps: [],
      escalationStepIndex: 0,
    };
  }
  const escalationSteps: number[] = [];
  if (configParameter.escalationLevels) {
    escalationSteps.push(
      ...configParameter.escalationLevels.map(
        (level) => Math.random() * (level.max - level.min) + level.min
      )
    );
  }
  return {
    uuid: uuidv4(),
    id: 0,
    type: value.type,
    name: value.name,
    texture: texture,
    width: configParameter.width,
    shape: configParameter.shape,
    position: value.position,
    rotation: value.rotation,
    scale: value.scale,
    escalationSteps: escalationSteps,
    escalationStepIndex: 0,
  };
}
