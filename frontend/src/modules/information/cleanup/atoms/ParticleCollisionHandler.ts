import { CollisionHandler } from '@/components/shared/atoms/game/CollisionHandler';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';

export class ParticleCollisionHandler implements CollisionHandler {
  handleCollision(gameObject: GameObject) {
    gameObject.notifyDestroy();
  }
}
