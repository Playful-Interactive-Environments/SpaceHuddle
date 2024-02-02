import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import GameContainerLite from '@/components/shared/atoms/game/GameContainerLite.vue';

export interface CustomObject {
  displayWidth: number;
  displayHeight: number;
  setGameContainer(gameContainer: GameContainer | GameContainerLite): void;
  calculateRelativePosition(): void;
}
