import { CollisionHandler } from '@/types/game/CollisionHandler';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameObjectLite from '@/components/shared/atoms/game/GameObjectLite.vue';

export class ParticleCollisionHandler implements CollisionHandler {
  handleCollision(gameObject: GameObject | GameObjectLite): boolean {
    gameObject.notifyCollision();
    return false;
    //gameObject.notifyDestroy();
    //return true;
  }
}
