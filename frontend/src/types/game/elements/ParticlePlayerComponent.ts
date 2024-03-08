import { ComponentOptionsMixin, DefineComponent, VNodeProps } from 'vue';
import {
  ParticleContainerEvents,
  ParticleContainerProps,
  AllowedPixiProps,
} from 'vue3-pixi';
import * as PIXI from 'pixi.js';
import * as PIXIParticles from '@pixi/particle-emitter';

/* eslint-disable @typescript-eslint/no-explicit-any*/
interface ParticlePlayerProps extends ParticleContainerProps {
  disabled: boolean;
  spaceX: number;
  spaceY: number;
  deepCloneConfig: boolean;
  autoUpdate: boolean;
  spriteSheetUrl: string | null;
  defaultTexture: PIXI.Texture | PIXI.Texture[] | null;
  config: PIXIParticles.EmitterConfigV3 | null;
  frequency: number;
}

export type ParticlePlayerComponent = DefineComponent<
  ParticlePlayerProps,
  any,
  unknown,
  any,
  any,
  ComponentOptionsMixin,
  ComponentOptionsMixin,
  (keyof ParticleContainerEvents)[],
  keyof ParticleContainerEvents,
  VNodeProps & AllowedPixiProps,
  Readonly<ParticlePlayerProps> & {
    [key in keyof ParticleContainerEvents as `on${Capitalize<key>}`]?:
      | ((...args: ParticleContainerEvents[key]) => any)
      | undefined;
  },
  any
>;
