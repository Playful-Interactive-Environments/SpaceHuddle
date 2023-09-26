<template>
  <div
    class="gameArea"
    v-if="playStateType === PlayStateType.play && levelType"
  >
    <GameContainer
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="false"
      :use-gravity="false"
      :background-texture="gameConfig.obstacles[levelType].settings.background"
      :background-position="BackgroundPosition.Cover"
      :background-movement="BackgroundMovement.Auto"
      @initRenderer="initRenderer"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <GameObject
            v-for="obstacle in obstacleList"
            :key="obstacle.uuid"
            v-model:id="obstacle.id"
            :type="obstacle.shape"
            :polygon-shape="obstacle.polygonShape"
            :collider-delta="20"
            :show-bounds="false"
            :anchor="obstacle.pivot"
            :object-space="ObjectSpace.RelativeToBackground"
            :x="obstacle.position[0]"
            :y="obstacle.position[1]"
            :rotation="obstacle.rotation"
            :scale="obstacle.scale"
            :options="{
              name: obstacle.name,
              collisionFilter: {
                group: -1,
              },
            }"
            :is-static="true"
            :clickable="false"
            :source="obstacle"
          >
            <CustomSprite
              :texture="obstacle.texture"
              :anchor="obstacle.pivot"
              :width="obstacle.width"
              :aspect-ration="getObjectAspect(obstacle.type, obstacle.name)"
              :object-space="ObjectSpace.RelativeToBackground"
            >
            </CustomSprite>
          </GameObject>
        </container>
      </template>
    </GameContainer>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as PIXI from 'pixi.js';
import { Prop, Watch } from 'vue-property-decorator';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer, {
  BackgroundPosition,
  BackgroundMovement,
} from '@/components/shared/atoms/game/GameContainer.vue';
import * as placeable from '@/types/game/Placeable';
import * as pixiUtil from '@/utils/pixi';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import CustomSprite from '@/components/shared/atoms/game/CustomSprite.vue';
import { until } from '@/utils/wait';
import * as tutorialService from '@/services/tutorial-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';
import * as cashService from '@/services/cash-service';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/coolit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as configParameter from '@/utils/game/configParameter';

/* eslint-disable @typescript-eslint/no-explicit-any*/
const tutorialType = 'find-it-object';

export interface MoleculeState {
  startCount: number;
  movedCount: number;
  hitCount: number;
  emitCount: number;
  decreaseCount: number;
}

export interface ObstacleState {
  totalCount: number;
  hitCount: number;
}

enum PlayStateType {
  play,
  win,
  lost,
}

export interface PlayStateResult {
  stars: number;
  time: number;
  moleculeState: { [key: string]: MoleculeState };
  obstacleState: { [key: string]: ObstacleState };
  hitCount: number;
  temperature: number;
}

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpace;
    },
    BackgroundPosition() {
      return BackgroundPosition;
    },
    BackgroundMovement() {
      return BackgroundMovement;
    },
  },
  components: {
    GameObject,
    GameContainer,
    CustomSprite,
  },
  emits: ['finished'],
})
export default class PlayLevel extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: null }) readonly level!: Idea | null;
  @Prop({ default: EndpointAuthorisationType.PARTICIPANT })
  authHeaderTyp!: EndpointAuthorisationType;
  renderer!: PIXI.Renderer;
  gameWidth = 0;
  gameHeight = 0;
  tutorialSteps: Tutorial[] = [];
  levelType = '';
  gameConfig = gameConfig;

  obstacleList: placeable.Placeable[] = [];
  stylesheets: { [key: string]: PIXI.Spritesheet } = {};
  startTime = Date.now();

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  mounted(): void {
    tutorialService.registerGetList(this.updateTutorial, this.authHeaderTyp);
  }

  updateTutorial(steps: Tutorial[]): void {
    this.tutorialSteps = steps.filter((step) => step.type === tutorialType);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTutorial);
  }

  get gameConfigTypes(): string[] {
    return configParameter.getGameConfigTypes(
      gameConfig.obstacles as any,
      this.levelType
    );
  }

  unmounted(): void {
    this.deregisterAll();
    for (const typeName of this.gameConfigTypes) {
      const settings =
        gameConfig.obstacles[this.levelType].categories[typeName].settings;
      pixiUtil.unloadTexture(settings.spritesheet);
    }
  }

  previousLevelType = '';
  isReadyForPlay = false;
  @Watch('level', { immediate: true })
  async onLevelChanged(): Promise<void> {
    if (this.level) {
      this.isReadyForPlay = false;
      this.obstacleList = [];
      const levelType = this.level.parameter.type
        ? this.level.parameter.type
        : configParameter.getDefaultLevelType(gameConfig.obstacles as any);
      if (this.previousLevelType && this.previousLevelType !== levelType) {
        const gameConfigTypes = Object.keys(
          gameConfig.obstacles[levelType].categories
        );
        for (const typeName of gameConfigTypes) {
          const previousSettings =
            gameConfig.obstacles[this.previousLevelType].categories[typeName]
              .settings;
          if (
            previousSettings &&
            previousSettings.spritesheet &&
            this.stylesheets[typeName] &&
            PIXI.Cache.has(previousSettings.spritesheet)
          ) {
            pixiUtil.unloadTexture(previousSettings.spritesheet);
            delete this.stylesheets[typeName];
          }
        }
      }
      this.levelType = levelType;
      const items = configParameter.getItemsForLevel(
        gameConfig.obstacles as any,
        this.level
      );
      const spriteSheetTypes = items
        .map((item) => item.type)
        .filter((value, index, array) => array.indexOf(value) === index);
      for (const spriteSheetType of spriteSheetTypes) {
        await until(() => this.spriteSheetLoaded(spriteSheetType));
      }
      this.obstacleList = items
        .filter((item) => this.hasTexture(item.type, item.name))
        .map((item) =>
          placeable.convertToDetailData(
            item,
            gameConfig.obstacles[levelType],
            this.getTexture(item.type, item.name)
          )
        );
      this.previousLevelType = levelType;
      this.startTime = Date.now();
      this.playStateType = PlayStateType.play;
      this.isReadyForPlay = true;
    }
  }

  @Watch('levelType', { immediate: true })
  onLevelTypeChanged(): void {
    if (this.levelType) {
      for (const typeName of this.gameConfigTypes) {
        const settings =
          gameConfig.obstacles[this.levelType].categories[typeName].settings;
        setTimeout(() => {
          if (
            settings &&
            settings.spritesheet &&
            this.previousLevelType !== this.levelType
          ) {
            pixiUtil.loadTexture(settings.spritesheet).then((sheet) => {
              this.stylesheets[typeName] = sheet;
            });
          }
        }, 100);
      }
    }
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
</style>
