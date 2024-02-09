import { CollisionHandler } from '@/types/game/CollisionHandler';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameObjectLite from '@/components/shared/atoms/game/GameObjectLite.vue';
import GameObjectLite2 from '@/components/shared/atoms/game/GameObjectLite2.vue';

export class ParticleCollisionHandler implements CollisionHandler {
  handleCollision(
    gameObject: GameObject | GameObjectLite | GameObjectLite2
  ): boolean {
    gameObject.notifyCollision();
    return false;
    //gameObject.notifyDestroy();
    //return true;
  }
}
