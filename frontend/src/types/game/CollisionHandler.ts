import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import { CollisionRegion } from '@/components/shared/atoms/game/GameContainer.vue';

export interface CollisionHandler {
  handleCollision(
    gameObject: GameObject,
    collisionObject: GameObject | CollisionRegion | null,
    hitPoint: [number, number]
  ): void;
}
