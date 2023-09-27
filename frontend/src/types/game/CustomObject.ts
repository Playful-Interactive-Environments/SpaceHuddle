import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';

export interface CustomObject {
  displayWidth: number;
  displayHeight: number;
  setGameContainer(gameContainer: GameContainer): void;
  calculateRelativePosition(): void;
}
