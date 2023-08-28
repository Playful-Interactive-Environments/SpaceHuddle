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
      :background-texture="gameConfig[levelType].settings.background"
      :background-position="BackgroundPosition.Cover"
      :background-movement="BackgroundMovement.Pan"
      @initRenderer="initRenderer"
      @gameObjectClick="gameObjectClick"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <GameObject
            v-for="placeable in placedObjects"
            :key="placeable.uuid"
            v-model:id="placeable.id"
            :type="placeable.shape"
            :collider-delta="20"
            :show-bounds="false"
            :object-space="ObjectSpace.RelativeToBackground"
            :x="placeable.position[0]"
            :y="placeable.position[1]"
            :rotation="placeable.rotation"
            :scale="placeable.scale"
            :options="{
              name: placeable.name,
              collisionFilter: {
                group: -1,
              },
            }"
            :is-static="true"
            :clickable="getCollectable(placeable)"
            :source="placeable"
            :mask="getSearchMask(placeable)"
            :trigger-delay="
              placeable.escalationSteps.length > placeable.escalationStepIndex
                ? placeable.escalationSteps[placeable.escalationStepIndex]
                : null
            "
            @handleTrigger="handleEscalation(placeable)"
          >
            <CustomSprite
              :texture="placeable.texture"
              :anchor="0.5"
              :width="placeable.width"
              :aspect-ration="getObjectAspect(placeable.type, placeable.name)"
              :object-space="ObjectSpace.RelativeToBackground"
            >
            </CustomSprite>
            <custom-particle-container
              v-if="placeable.escalationStepIndex > 0"
              :config="getConfigForPlaceable(placeable)"
            />
          </GameObject>
          <GameObject
            v-if="gameWidth > 0"
            :moveWithBackground="false"
            v-model:x="searchPosition[0]"
            v-model:y="searchPosition[1]"
            type="circle"
            :options="{
              name: 'searchObject',
              collisionFilter: {
                group: -1,
              },
            }"
          >
            <Graphics @render="drawSearchObject" />
          </GameObject>
          <Graphics
            v-if="noneCollectableObjects.length > 0"
            ref="searchMask"
            @render="drawSearchMask"
          />
        </container>
      </template>
    </GameContainer>
    <div class="overlay-bottom">{{ collectedCount }} / {{ totalCount }}</div>
    <DrawerBottomOverlay
      v-if="
        clickedPlaceableConfig &&
        hasTexture(clickedPlaceable.type, clickedPlaceable.name)
      "
      v-model="showToolbox"
      :title="
        $t(
          `${clickedPlaceableConfigSettings.explanationText}.${clickedPlaceable.type}.${clickedPlaceableConfig.explanationKey}.name`
        )
      "
    >
      <SpriteCanvas
        :texture="getTexture(clickedPlaceable.type, clickedPlaceable.name)"
        :aspect-ration="
          getObjectAspect(clickedPlaceable.type, clickedPlaceable.name)
        "
        :width="gameWidth - 50"
        :height="(gameHeight / 10) * 4"
        :background-color="backgroundColor"
      />
      <div>
        {{
          $t(
            `${clickedPlaceableConfigSettings.explanationText}.${clickedPlaceable.type}.${clickedPlaceableConfig.explanationKey}.description`
          )
        }}
      </div>
    </DrawerBottomOverlay>
  </div>
  <div
    class="gameArea result"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.win"
  >
    <span>{{ $t('module.information.findit.participant.win') }}</span>
  </div>
  <div
    class="gameArea result"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.lost"
  >
    <span>{{ $t('module.information.findit.participant.lost') }}</span>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as PIXI from 'pixi.js';
import * as PIXIParticles from '@pixi/particle-emitter';
import { Prop, Watch } from 'vue-property-decorator';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer, {
  BackgroundPosition,
  BackgroundMovement,
} from '@/components/shared/atoms/game/GameContainer.vue';
import * as placeable from '@/modules/information/findit/types/Placeable';
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
import * as escalationConfig from '@/modules/information/findit/data/escalation.json';
import CustomParticleContainer from '@/components/shared/atoms/game/CustomParticleContainer.vue';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/information/findit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as configParameter from '@/modules/information/findit/utils/configParameter';

const tutorialType = 'find-it-object';

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
    BackgroundMovement() {
      return BackgroundMovement;
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

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PlayState extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: null }) readonly level!: Idea | null;
  @Prop({ default: '100%' }) readonly height!: string;
  @Prop({ default: EndpointAuthorisationType.PARTICIPANT })
  authHeaderTyp!: EndpointAuthorisationType;
  renderer!: PIXI.Renderer;
  gameWidth = 0;
  gameHeight = 0;
  showToolbox = false;
  tutorialSteps: Tutorial[] = [];
  clickedPlaceable: placeable.Placeable | null = null;
  levelType = '';
  gameConfig = gameConfig;

  placedObjects: placeable.Placeable[] = [];
  collectedObjects: placeable.PlaceableBase[] = [];
  stylesheets: { [key: string]: PIXI.Spritesheet } = {};
  totalCount = 0;
  collectedCount = 0;
  searchPosition: [number, number] = [0, 0];
  startTime = Date.now();

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;

  get playStateResult(): PlayStateResult {
    return {
      stars: Math.floor((this.collectedCount / this.totalCount) * 3),
      time: Date.now() - this.startTime,
      collected: this.collectedCount,
      total: this.totalCount,
      itemList: this.collectedObjects,
    };
  }

  get escalationLevelObject(): placeable.Placeable[][] {
    const itemList = this.noneCollectableObjects;
    const levelEscalationList = itemList
      .map((item) => item.escalationStepIndex)
      .filter((value, index, array) => array.indexOf(value) === index);
    return levelEscalationList.map((level) =>
      itemList.filter((item) => item.escalationStepIndex === level)
    );
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

  get gameConfigTypes(): string[] {
    return configParameter.getGameConfigTypes(this.levelType);
  }

  unmounted(): void {
    this.deregisterAll();
    for (const typeName of this.gameConfigTypes) {
      const settings = gameConfig[this.levelType][typeName].settings;
      PIXI.Assets.unload(settings.spritesheet);
    }
  }

  getSearchMask(placeable: placeable.Placeable): any {
    const config = gameConfig[this.levelType][placeable.type].settings;
    if (!config.collectable) {
      return this.$refs.searchMask;
    }
    return null;
  }

  drawSearchObject(graphics: PIXI.Graphics): void {
    graphics.clear();
    graphics.lineStyle(10, '#ff0000');
    graphics.drawCircle(0, 0, this.gameWidth / 10);
  }

  searchGraphics!: PIXI.Graphics;
  drawSearchMask(graphics: PIXI.Graphics): void {
    this.searchGraphics = graphics;
    this.updatedSearchMask();
  }

  updatedSearchMask(): void {
    if (this.searchGraphics) {
      this.searchGraphics.clear();
      this.searchGraphics.beginFill('#ffffff');
      this.searchGraphics.drawRect(0, 0, this.gameWidth, this.gameHeight);
      this.searchGraphics.beginHole();
      this.searchGraphics.drawCircle(
        this.searchPosition[0],
        this.searchPosition[1],
        this.gameWidth / 10
      );
      this.searchGraphics.endHole();
      this.searchGraphics.endFill();
    }
  }

  @Watch('searchPosition', { immediate: true, deep: true })
  onSearchPositionChanged(): void {
    this.updatedSearchMask();
  }

  @Watch('gameWidth', { immediate: true })
  onGameWidthSet(): void {
    this.searchPosition = [this.gameWidth / 2, this.gameHeight / 2];
  }

  previousLevelType = '';
  @Watch('level', { immediate: true })
  async onLevelChanged(): Promise<void> {
    if (this.level) {
      this.placedObjects = [];
      const levelType = this.level.parameter.type
        ? this.level.parameter.type
        : configParameter.getDefaultLevelType();
      if (this.previousLevelType && this.previousLevelType !== levelType) {
        const gameConfigTypes = Object.keys(gameConfig[levelType]).filter(
          (config) => config !== 'settings'
        );
        for (const typeName of gameConfigTypes) {
          const previousSettings =
            gameConfig[this.previousLevelType][typeName].settings;
          if (
            previousSettings &&
            previousSettings.spritesheet &&
            this.stylesheets[typeName] &&
            PIXI.Cache.has(previousSettings.spritesheet)
          ) {
            PIXI.Assets.unload(previousSettings.spritesheet);
            delete this.stylesheets[typeName];
          }
        }
      }
      this.levelType = levelType;
      const items = configParameter.getItemsForLevel(this.level);
      const spriteSheetTypes = items
        .map((item) => item.type)
        .filter((value, index, array) => array.indexOf(value) === index);
      for (const spriteSheetType of spriteSheetTypes) {
        await until(() => this.spriteSheetLoaded(spriteSheetType));
      }
      this.placedObjects = items
        .filter((item) => this.hasTexture(item.type, item.name))
        .map((item) =>
          placeable.convertToDetailData(
            item,
            gameConfig[levelType],
            this.getTexture(item.type, item.name)
          )
        );
      this.previousLevelType = levelType;
      this.totalCount = this.collectableObjects.length;
      this.playStateType = PlayStateType.play;
    }
  }

  @Watch('levelType', { immediate: true })
  onLevelTypeChanged(): void {
    if (this.levelType) {
      for (const typeName of this.gameConfigTypes) {
        const settings = gameConfig[this.levelType][typeName].settings;
        setTimeout(() => {
          if (
            settings &&
            settings.spritesheet &&
            this.previousLevelType !== this.levelType
          ) {
            PIXI.Assets.load(settings.spritesheet).then((sheet) => {
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

  get collectableObjects(): placeable.Placeable[] {
    return this.placedObjects.filter(
      (obj) => gameConfig[this.levelType][obj.type].settings.collectable
    );
  }

  get noneCollectableObjects(): placeable.Placeable[] {
    return this.placedObjects.filter(
      (obj) => !gameConfig[this.levelType][obj.type].settings.collectable
    );
  }

  @Watch('placedObjects.length', { immediate: false })
  onPlaceablesCountChanged(): void {
    if (this.totalCount > 0 && this.collectableObjects.length === 0) {
      this.$emit('playFinished', this.playStateResult);
      if (this.placedObjects.length > 0) this.playStateType = PlayStateType.win;
    }
  }

  get clickedPlaceableConfigSettings(): any {
    if (this.clickedPlaceable) {
      return gameConfig[this.levelType][this.clickedPlaceable.type].settings;
    }
    return null;
  }

  get clickedPlaceableConfig(): any {
    if (this.clickedPlaceable) {
      return gameConfig[this.levelType][this.clickedPlaceable.type][
        this.clickedPlaceable.name
      ];
    }
    return null;
  }

  getCollectable(placeable: placeable.Placeable): boolean {
    const config = gameConfig[this.levelType][placeable.type].settings;
    return config.collectable;
  }

  destroyPlaceable(value: placeable.Placeable, event: PointerEvent): void {
    const config = gameConfig[this.levelType][value.type].settings;
    if (config.explanationText) this.clickedPlaceable = value;
    if (config.collectable) {
      const id = value.id;
      const index = this.placedObjects.findIndex((p) => p.id === id);
      if (index > -1) {
        this.collectedObjects.push(placeable.convertToBase(value));
        this.placedObjects.splice(index, 1);
      }
      this.collectedCount++;
    }
    const placeableConfig = gameConfig[this.levelType][value.type][value.name];
    if (placeableConfig.explanationKey) {
      const tutorialStepName = `${value.type}-${placeableConfig.explanationKey}`;
      if (!this.tutorialSteps.find((item) => item.step === tutorialStepName)) {
        event.preventDefault();
        this.showToolbox = true;
        const tutorialItem: Tutorial = {
          step: tutorialStepName,
          type: tutorialType,
          order: 0,
        };
        tutorialService.addTutorialStep(
          tutorialItem,
          this.authHeaderTyp,
          this.eventBus
        );
      }
    }
  }

  gameObjectClick(objectList: GameObject[], event: PointerEvent): void {
    for (const obj of objectList) {
      if (obj.source) this.destroyPlaceable(obj.source, event);
    }
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
  }

  handleEscalation(placeable: placeable.Placeable): void {
    placeable.escalationStepIndex++;
    if (placeable.escalationStepIndex >= placeable.escalationSteps.length) {
      ElMessage({
        message: this.$t('module.information.findit.participant.lost'),
        type: 'error',
        center: true,
        showClose: true,
        onClose: () => {
          this.$emit('playFinished', this.playStateResult);
          this.playStateType = PlayStateType.lost;
        },
      });
    }
  }

  getConfigForPlaceable(
    placeable: placeable.Placeable
  ): PIXIParticles.EmitterConfigV3 | null {
    const typeConfig = gameConfig[this.levelType][placeable.type].settings;
    if (!typeConfig.escalationShape) return null;
    let shapeIndex = placeable.escalationStepIndex - 1;
    if (shapeIndex < 0) return null;
    if (shapeIndex >= typeConfig.escalationShape.length)
      shapeIndex = typeConfig.escalationShape.length - 1;
    return escalationConfig[typeConfig.escalationShape[shapeIndex]];
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
