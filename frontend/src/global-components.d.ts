import { DefineComponent } from 'vue';

declare module '@vue/runtime-core' {
  export interface GlobalComponents {
    SpriteConverter;
    GameObjectTest;
    ParticlePlayer;
  }
}
