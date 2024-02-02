import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameObjectLite from '@/components/shared/atoms/game/GameObjectLite.vue';
import { CollisionRegion } from '@/components/shared/atoms/game/GameContainer.vue';
import * as Matter from 'matter-js/build/matter';

export interface CollisionHandler {
  handleCollision(
    gameObject: GameObject | GameObjectLite,
    collisionObject: GameObject | GameObjectLite | CollisionRegion | null,
    hitPoint: [number, number],
    hitPointScreen: [number, number],
    objectBody: Matter.Body,
    collisionBody: Matter.Body
  ): boolean;
}
