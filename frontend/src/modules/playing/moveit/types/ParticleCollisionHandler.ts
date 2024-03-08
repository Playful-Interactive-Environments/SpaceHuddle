import { CollisionHandler } from '@/types/game/CollisionHandler';
import GameObject from '@/types/game/gameObject/GameObject';

export class ParticleCollisionHandler implements CollisionHandler {
  handleCollision(gameObject: GameObject): boolean {
    gameObject.physcics.notifyCollision();
    return false;
  }
}
