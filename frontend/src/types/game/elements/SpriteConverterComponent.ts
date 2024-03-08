import { ComponentOptionsMixin, DefineComponent, VNodeProps } from 'vue';
import { SpriteEvents, SpriteProps, AllowedPixiProps } from 'vue3-pixi';
import * as PIXI from 'pixi.js';
import { ObjectSpaceType } from '@/types/enum/ObjectSpaceType';

/* eslint-disable @typescript-eslint/no-explicit-any*/
interface SpriteConverterProps extends SpriteProps {
  spaceX: number;
  spaceY: number;
  objectSpace: ObjectSpaceType;
  spaceWidth: number | undefined;
  spaceHeight: number | undefined;
  aspectRation: number;
  anchor: number | [number, number];
  tint: string;
  preTint: string;
  alpha: number;
  colorOverlay: [number, number, number, number];
  outline: number | null;
  outlineWidth: number;
  saturation: number;
  texture: string | PIXI.Texture;
  preRenderFilters: boolean;
  customFilters: any[];
  preFilters: any[];
}

export type SpriteConverterComponent = DefineComponent<
  SpriteConverterProps,
  any,
  unknown,
  any,
  any,
  ComponentOptionsMixin,
  ComponentOptionsMixin,
  (keyof SpriteEvents)[],
  keyof SpriteEvents,
  VNodeProps & AllowedPixiProps,
  Readonly<SpriteConverterProps> & {
    [key in keyof SpriteEvents as `on${Capitalize<key>}`]?:
      | ((...args: SpriteEvents[key]) => any)
      | undefined;
  },
  any
>;
