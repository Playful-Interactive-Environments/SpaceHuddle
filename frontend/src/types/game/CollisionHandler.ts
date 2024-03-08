import GameObject from '@/types/game/gameObject/GameObject';
import { CollisionRegion } from '@/components/shared/atoms/game/GameContainer.vue';
import * as Matter from 'matter-js/build/matter';

export interface CollisionHandler {
  handleCollision(
    gameObject: GameObject,
    collisionObject: GameObject | CollisionRegion | null,
    hitPoint: [number, number],
    hitPointScreen: [number, number],
    objectBody: Matter.Body,
    collisionBody: Matter.Body
  ): boolean;
}
