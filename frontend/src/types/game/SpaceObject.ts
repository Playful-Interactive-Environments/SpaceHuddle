import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import GameContainerLite from '@/components/shared/atoms/game/GameContainerLite.vue';
import GameContainerLite2 from '@/components/shared/atoms/game/GameContainerLite2.vue';
import GameContainerLite3 from '@/components/shared/atoms/game/GameContainerLite3.vue';

export interface SpaceObject {
  setGameContainer(
    gameContainer:
      | GameContainer
      | GameContainerLite
      | GameContainerLite2
      | GameContainerLite3
  ): void;
  calculateRelativePosition(): void;
}
