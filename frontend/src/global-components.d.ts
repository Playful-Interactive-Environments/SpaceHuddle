//Do not delete this line even if it is probably not used. Otherwise you will get an error with every import from 'vue' statement.
import { DefineComponent } from 'vue';
import { GameObjectComponent } from '@/types/game/elements/GameObjectComponent';
import { SpriteConverterComponent } from '@/types/game/elements/SpriteConverterComponent';
import { ParticlePlayerComponent } from '@/types/game/elements/ParticlePlayerComponent.ts';

declare module '@vue/runtime-core' {
  export interface GlobalComponents {
    SpriteConverter: SpriteConverterComponent;
    GameObject: GameObjectComponent;
    ParticlePlayer: ParticlePlayerComponent;
  }
}
