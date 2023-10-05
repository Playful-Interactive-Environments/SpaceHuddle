<template>
  <div
    class="gameArea"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.play"
  >
    <GameContainer
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="false"
      :use-gravity="false"
      :background-texture="gameConfig.gameValues.background"
      :background-position="BackgroundPosition.Cover"
      @initRenderer="initRenderer"
      @gameObjectClick="gameObjectClick"
    >
      <template v-slot:default>
        <container v-if="gameWidth">

        </container>
      </template>
    </GameContainer>
  </div>
  <div
    class="gameArea result"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.win"
  >
    <span>{{ $t('module.playing.shopit.participant.win') }}</span>
  </div>
  <div
    class="gameArea result"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.lost"
  >
    <span>{{ $t('module.playing.shopit.participant.lost') }}</span>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as PIXI from 'pixi.js';
import { Prop, Watch } from 'vue-property-decorator';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer, {
  BackgroundPosition,
} from '@/components/shared/atoms/game/GameContainer.vue';
import * as placeable from '@/types/game/Placeable';
import * as pixiUtil from '@/utils/pixi';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import CustomSprite from '@/components/shared/atoms/game/CustomSprite.vue';
import { until } from '@/utils/wait';
import DrawerBottomOverlay from '@/components/participant/molecules/DrawerBottomOverlay.vue';
import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import * as tutorialService from '@/services/tutorial-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';
import * as cashService from '@/services/cash-service';
import { ElMessage } from 'element-plus';
import CustomParticleContainer from '@/components/shared/atoms/game/CustomParticleContainer.vue';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/shopit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as configParameter from '@/utils/game/configParameter';

/* eslint-disable @typescript-eslint/no-explicit-any*/
const tutorialType = 'shop-it-object';

enum PlayStateType {
  play,
  win,
  lost,
}

export interface PlayStateResult {
  stars: number;
  time: number;
  collected: number;
  total: number;
  itemList: placeable.PlaceableBase[];
}

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpace;
    },
    BackgroundPosition() {
      return BackgroundPosition;
    },
  },
  components: {
    CustomParticleContainer,
    GameObject,
    GameContainer,
    CustomSprite,
    DrawerBottomOverlay,
    SpriteCanvas,
  },
  emits: ['playFinished'],
})
export default class PlayState extends Vue {
  @Prop() readonly taskId!: string;
  //@Prop({ default: null }) readonly level!: Idea | null;
  @Prop({ default: '100%' }) readonly height!: string;
  @Prop({ default: EndpointAuthorisationType.PARTICIPANT })
  authHeaderTyp!: EndpointAuthorisationType;
  renderer!: PIXI.Renderer;
  gameWidth = 0;
  gameHeight = 0;
  showToolbox = false;
  tutorialSteps: Tutorial[] = [];
  levelType = '';
  gameConfig = gameConfig;

  collectedObjects: placeable.PlaceableBase[] = [];
  stylesheets: { [key: string]: PIXI.Spritesheet } = {};
  totalCount = 0;
  collectedCount = 0;
  startTime = Date.now();

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;

  clearPlayState(): void {
    this.levelType = '';
    this.collectedObjects = [];
    this.totalCount = 0;
    this.collectedCount = 0;
    this.startTime = Date.now();
  }

  get playStateResult(): PlayStateResult {
    return {
      stars: Math.floor((this.collectedCount / this.totalCount) * 3),
      time: Date.now() - this.startTime,
      collected: this.collectedCount,
      total: this.totalCount,
      itemList: this.collectedObjects,
    };
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  mounted(): void {
    tutorialService.registerGetList(this.updateTutorial, this.authHeaderTyp);

    /*this.eventBus.off(EventType.CHANGE_TUTORIAL);
    this.eventBus.on(EventType.CHANGE_TUTORIAL, async (steps) => {
      this.updateTutorial(steps as Tutorial[]);
    });*/
  }

  updateTutorial(steps: Tutorial[]): void {
    this.tutorialSteps = steps.filter((step) => step.type === tutorialType);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTutorial);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  getSpriteSheetForType(objectType: string): PIXI.Spritesheet {
    return this.stylesheets[objectType];
  }

  getTexture(objectType: string, objectName: string): PIXI.Texture | string {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    if (spriteSheet && spriteSheet.textures)
      return spriteSheet.textures[objectName];
    return '';
  }

  spriteSheetLoaded(objectType: string): boolean {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    return !!spriteSheet && !!spriteSheet.textures;
  }

  hasTexture(objectType: string, objectName: string): boolean {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    return (
      !!spriteSheet &&
      !!spriteSheet.textures &&
      objectName in spriteSheet.textures
    );
  }

  getObjectAspect(objectType: string, objectName: string): number {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    return pixiUtil.getSpriteAspect(spriteSheet, objectName);
  }

  gameObjectClick(objectList: GameObject[], event: PointerEvent): void {
    for (const obj of objectList) {
      console.log("gameObjectClicked");
    }
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
  }
}
</script>

<style scoped lang="scss">
.gameArea {
  height: calc(100%);
  width: 100%;
  position: relative;
}

.custom-renderer-wrapper {
  height: 100%;
}

.overlay-bottom {
  pointer-events: none;
  position: absolute;
  z-index: 100;
  bottom: 1rem;
  right: 1rem;
  left: 1rem;
  text-align: center;
  font-size: var(--font-size-xxlarge);
  color: white;
}

.result {
  font-size: var(--font-size-xxlarge);
  display: flex;
  align-items: center;

  span {
    width: 100%;
    text-align: center;
  }
}
</style>
