import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameObjectLite from '@/components/shared/atoms/game/GameObjectLite.vue';
import GameObjectLite2 from '@/components/shared/atoms/game/GameObjectLite2.vue';
import { CollisionRegion } from '@/components/shared/atoms/game/GameContainer.vue';
import * as Matter from 'matter-js/build/matter';

export interface CollisionHandler {
  handleCollision(
    gameObject: GameObject | GameObjectLite | GameObjectLite2,
    collisionObject:
      | GameObject
      | GameObjectLite
      | GameObjectLite2
      | CollisionRegion
      | null,
    hitPoint: [number, number],
    hitPointScreen: [number, number],
    objectBody: Matter.Body,
    collisionBody: Matter.Body
  ): boolean;
}
