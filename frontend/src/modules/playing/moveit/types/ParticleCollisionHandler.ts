import { CollisionHandler } from '@/types/game/CollisionHandler';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';

export class ParticleCollisionHandler implements CollisionHandler {
  handleCollision(gameObject: GameObject): boolean {
    gameObject.notifyCollision();
    return false;
    //gameObject.notifyDestroy();
    //return true;
  }
}
