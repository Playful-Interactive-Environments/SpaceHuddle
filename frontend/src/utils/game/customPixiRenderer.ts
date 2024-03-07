import {
  renderer,
  RendererOptions,
  patchProp as defuPatchProp,
} from 'vue3-pixi';
import * as PIXI from 'pixi.js';
import { GameObject as GameObjectTest } from '@/types/game/gameObject/GameObject';
import { SpriteConverter } from '@/types/game/sprite/SpriteConverter';
import ParticlePlayer from '@/types/game/ParticlePlayer';

const ContainerRender: RendererOptions = {
  name: 'Container',
  createElement: () => new PIXI.Container(),
  remove: (node) => {
    node.destroy({ children: true });
  },
};

const GameObjectRender: RendererOptions = {
  name: 'GameObjectTest',
  createElement: () => {
    return new GameObjectTest();
  },
};

const SpriteConverterRender: RendererOptions = {
  name: 'SpriteConverter',
  createElement: (props) => {
    return new SpriteConverter(props.texture);
  },
};

const ParticlePlayerRender: RendererOptions = {
  name: 'ParticlePlayer',
  createElement: (props) =>
    new ParticlePlayer(props['max-size'] || props.maxSize, props.properties),
  patchProp: (el: ParticlePlayer, key, prev, next) => {
    switch (key) {
      case 'max-size':
      case 'properties':
        break;
      default:
        defuPatchProp(el, key, prev, next);
    }
  },
};

export default function initCustomPixiRenderer(): void {
  renderer.use([
    ContainerRender,
    GameObjectRender,
    SpriteConverterRender,
    ParticlePlayerRender,
  ]);
}
