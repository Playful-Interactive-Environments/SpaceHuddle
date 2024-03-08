import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';

export interface SpaceObject {
  setGameContainer(gameContainer: GameContainer): void;
  calculateRelativePosition(): void;
}
