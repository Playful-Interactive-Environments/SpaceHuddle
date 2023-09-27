import GameObject from '@/components/shared/atoms/game/GameObject.vue';

export interface CollisionHandler {
  handleCollision(
    gameObject: GameObject,
    collisionObject: GameObject | null
  ): void;
}
