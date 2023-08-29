import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';

export interface CustomObject {
  setGameContainer(gameContainer: GameContainer): void;
  calculateRelativePosition(): void;
}
