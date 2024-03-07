//Do not delete this line even if it is probably not used. Otherwise you will get an error with every import from 'vue' statement.
import { DefineComponent } from 'vue';

declare module '@vue/runtime-core' {
  export interface GlobalComponents {
    SpriteConverter;
    GameObjectTest;
    ParticlePlayer;
  }
}
