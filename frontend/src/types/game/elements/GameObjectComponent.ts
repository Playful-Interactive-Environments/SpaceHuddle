import GameObject from '@/types/game/gameObject/GameObject';
import * as Matter from 'matter-js/build/matter';
import { ComponentOptionsMixin, DefineComponent, VNodeProps } from 'vue';
import { ContainerEvents, AllowedPixiProps } from 'vue3-pixi';
import { ObjectSpaceType } from '@/types/enum/ObjectSpaceType';
import { CollisionHandler } from '@/types/game/CollisionHandler';
import {
  IGameObjectSource,
  FastObjectBehaviour,
  ConditionalVelocity,
} from '@/types/game/gameObject/GameObject';
import * as PIXI from 'pixi.js';

/* eslint-disable @typescript-eslint/no-explicit-any*/
interface GameObjectProps {
  posX: number;
  posY: number;
  fixSize: [number, number] | number | null;
  angle: number;
  scale: number;
  objectSpace: ObjectSpaceType;
  shape: 'rect' | 'circle' | 'polygon';
  polygonShape: [number, number][];
  colliderDelta: number;
  showBounds: boolean;
  options: {
    [key: string]: string | number | boolean | object;
  };
  isStatic: boolean;
  isActive: boolean;
  clickable: boolean;
  collisionHandler: CollisionHandler;
  source: IGameObjectSource | null;
  usePhysic: boolean;
  keepInside: boolean;
  affectedByForce: boolean;
  moveWithBackground: boolean;
  triggerDelay: number | null;
  triggerDelayPause: boolean;
  disabled: boolean;
  fastObjectBehaviour: FastObjectBehaviour;
  sleepIfNotVisible: boolean;
  objectAnchor: number | [number, number];
  zIndex: number;
  conditionalVelocity: ConditionalVelocity | null;
  mask: PIXI.Container<PIXI.DisplayObject> | PIXI.MaskData | null;
  poolingKey: string;
  highlighted: boolean;
}

export interface GameObjectEvents extends ContainerEvents {
  notify_collision: [GameObject];
  outside_drawing_space: [
    gameObjec: GameObject,
    bounds: {
      right: number;
      left: number;
      bottom: number;
      top: number;
    }
  ];
  size_changed: [[number, number]];
  collision: [
    gameObject: GameObject,
    collisionObject: any | null,
    objectBody: Matter.Body,
    collisionBody: Matter.Body,
    hitPoint: [number, number],
    hitPointScreen: [number, number]
  ];
  hold: [GameObject];
  release: [GameObject];
  handle_trigger: [GameObject];
  position_changed: [[number, number]];
  initialised: [GameObject];
  is_part_of_chain_changed: [gameObject: GameObject, isPart: boolean];
  init_error: [GameObject];
  visibility_changed: [boolean];
  highlighted_changed: [value: boolean, gameObject: GameObject];
}

export type GameObjectComponent = DefineComponent<
  GameObjectProps,
  any,
  unknown,
  any,
  any,
  ComponentOptionsMixin,
  ComponentOptionsMixin,
  (keyof GameObjectEvents)[],
  keyof GameObjectEvents,
  VNodeProps & AllowedPixiProps,
  Readonly<GameObjectProps> & {
    [key in keyof GameObjectEvents as `on${Capitalize<key>}`]?:
      | ((...args: GameObjectEvents[key]) => any)
      | undefined;
  },
  any
>;
