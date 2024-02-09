import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import GameContainerLite from '@/components/shared/atoms/game/GameContainerLite.vue';
import GameContainerLite2 from '@/components/shared/atoms/game/GameContainerLite2.vue';

export interface CustomObject {
  displayWidth: number;
  displayHeight: number;
  setGameContainer(
    gameContainer: GameContainer | GameContainerLite | GameContainerLite2
  ): void;
  calculateRelativePosition(): void;
}
