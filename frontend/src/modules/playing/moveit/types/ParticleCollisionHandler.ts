import { CollisionHandler } from '@/types/game/CollisionHandler';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
//import GameObjectLite from '@/components/shared/atoms/game/GameObjectLite.vue';
//import GameObjectLite2 from '@/components/shared/atoms/game/GameObjectLite2.vue';
//import GameObjectLite3 from '@/components/shared/atoms/game/GameObjectLite3.vue';

export class ParticleCollisionHandler implements CollisionHandler {
  handleCollision(
    gameObject: GameObject //| GameObjectLite | GameObjectLite2 | GameObjectLite3
  ): boolean {
    gameObject.notifyCollision();
    return false;
    //gameObject.notifyDestroy();
    //return true;
  }
}
