<template>
  <div
    class="gameArea"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.play"
  >
    <GameContainer
      ref="gameContainer"
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="false"
      :use-gravity="false"
      :background-texture="gameConfig[levelType].settings.background"
      :background-position="BackgroundPosition.Cover"
      :background-movement="BackgroundMovement.Map"
      @initRenderer="initRenderer"
      @gameObjectClick="gameObjectClick"
      @updateOffset="offsetChanged"
      :collision-borders="CollisionBorderType.Screen"
      :border-delta="-searchSize"
      :pixi-filter-list="[zoomFilter]"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <GameObject
            v-for="placeable in placedObjects"
            :key="placeable.uuid"
            :type="placeable.shape"
            :collider-delta="20"
            :show-bounds="false"
            :object-space="ObjectSpace.RelativeToBackground"
            :posX="getDisplayPosition(placeable)[0]"
            :posY="getDisplayPosition(placeable)[1]"
            :colliderScaleFactor="getDisplayColliderScaleFactor(placeable)"
            :angle="placeable.rotation"
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
            :trigger-delay-pause="showToolbox"
            @handle_trigger="handleEscalation(placeable)"
          >
            <SpriteConverter
              :texture="placeable.texture"
              :anchor="0.5"
              :space-width="placeable.width"
              :aspect-ration="getObjectAspect(placeable.type, placeable.name)"
              :object-space="ObjectSpace.RelativeToBackground"
              :saturation="placeable.saturation"
            >
            </SpriteConverter>
            <ParticlePlayer
              v-if="placeable.escalationStepIndex > 0"
              :config="getConfigForPlaceable(placeable)"
            />
          </GameObject>
          <Graphics ref="searchMask" @render="drawSearchMask" />
          <GameObject
            v-if="gameWidth > 0"
            ref="searchObj"
            :moveWithBackground="false"
            :posX="gameWidth / 2"
            :posY="gameHeight / 2"
            type="circle"
            :z-index="10"
            :options="{
              name: 'searchObject',
              collisionFilter: {
                group: -1,
              },
            }"
            @position_changed="onSearchPositionChanged"
          >
            <Graphics @render="drawSearchObject" />
          </GameObject>
        </container>
      </template>
    </GameContainer>
    <div class="overlay-bottom">{{ collectedCount }} / {{ totalCount }}</div>
    <DrawerBottomOverlay
      v-if="
        clickedPlaceableConfig &&
        infoPlaceableList.length > 0 &&
        hasTexture(infoPlaceableList[0].type, infoPlaceableList[0].name)
      "
      v-model="showToolbox"
      :title="
        $t(
          `${clickedPlaceableConfigSettings.explanationText}.${infoPlaceableList[0].type}.${clickedPlaceableConfig.explanationKey}.name`
        )
      "
    >
      <div
        v-if="clickedPlaceableConfigSettings.collectable"
        class="collected-right"
      >
        {{ $t('module.playing.findit.participant.collectable') }}
      </div>
      <div
        v-else-if="clickedPlaceableConfig.notSearched"
        class="collected-wrong"
      >
        {{ $t('module.playing.findit.participant.not-searched') }}
      </div>
      <div v-else class="collected-wrong">
        {{ $t('module.playing.findit.participant.not-collectable') }}
      </div>
      <div class="clickedPlaceable">
        <img
          v-if="
            levelTypeImages[infoPlaceableList[0].type] &&
            levelTypeImages[infoPlaceableList[0].type][
              infoPlaceableList[0].name
            ]
          "
          :src="
            levelTypeImages[infoPlaceableList[0].type][
              infoPlaceableList[0].name
            ]
          "
          :alt="infoPlaceableList[0].name"
          :style="{
            'max-width': `calc(${gameWidth}px - 6rem)`,
            'max-height': `${(gameHeight / 10) * 4}px`,
          }"
        />
      </div>
      <div>
        {{
          $t(
            `${clickedPlaceableConfigSettings.explanationText}.${infoPlaceableList[0].type}.${clickedPlaceableConfig.explanationKey}.description`
          )
        }}
      </div>
    </DrawerBottomOverlay>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import * as PIXI from 'pixi.js';
import * as PIXIParticles from '@pixi/particle-emitter';
import { Prop, Watch } from 'vue-property-decorator';
import GameObject, {
  IGameObjectSource,
} from '@/types/game/gameObject/GameObject';
import GameContainer, {
  BackgroundPosition,
  BackgroundMovement,
  CollisionBorderType,
} from '@/components/shared/atoms/game/GameContainer.vue';
import * as placeable from '@/types/game/Placeable';
import * as pixiUtil from '@/utils/pixi';
import { ObjectSpaceType } from '@/types/enum/ObjectSpaceType';
import { until } from '@/utils/wait';
import DrawerBottomOverlay from '@/components/participant/molecules/DrawerBottomOverlay.vue';
import * as tutorialService from '@/services/tutorial-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Tutorial } from '@/types/api/Tutorial';
import * as cashService from '@/services/cash-service';
import { ElMessage } from 'element-plus';
import escalationConfig from '@/modules/playing/findit/data/escalation.json';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/findit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as configParameter from '@/utils/game/configParameter';
import { BulgePinchFilter } from 'pixi-filters';

/* eslint-disable @typescript-eslint/no-explicit-any*/
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
  classified: number;
  correctClassified: number;
  itemList: placeable.PlaceableBase[];
  redHerringList: placeable.PlaceableBase[];
}

interface FindItPlaceable extends placeable.Placeable, IGameObjectSource {
  escalationSteps: number[];
  escalationStepIndex: number;
}

function convertToFindItPlaceable(
  value: placeable.PlaceableBase,
  categoryConfig: placeable.PlaceableThemeConfig,
  texture: string | PIXI.Texture
): FindItPlaceable {
  const result = placeable.convertToDetailData(value, categoryConfig, texture);
  const escalationSteps: number[] = [];
  const configParameter = placeable.getConfigParameter(
    value,
    categoryConfig
  ) as any;
  if (configParameter) {
    if (configParameter.escalationLevels) {
      escalationSteps.push(
        ...configParameter.escalationLevels.map(
          (level) => Math.random() * (level.max - level.min) + level.min
        )
      );
    }
  }
  return {
    gameObject: null,
    uuid: result.uuid,
    type: result.type,
    name: result.name,
    texture: result.texture,
    width: result.width,
    shape: result.shape,
    polygonShape: result.polygonShape,
    pivot: result.pivot,
    position: result.position,
    rotation: result.rotation,
    scale: result.scale,
    escalationSteps: escalationSteps,
    escalationStepIndex: 0,
    placingRegions: result.placingRegions,
    saturation: result.saturation ?? 1,
    zIndex: 0,
  };
}

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpaceType;
    },
    BackgroundPosition() {
      return BackgroundPosition;
    },
    BackgroundMovement() {
      return BackgroundMovement;
    },
  },
  components: {
    DrawerBottomOverlay,
  },
  emits: ['playFinished'],
})
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
  levelType = '';
  gameConfig = gameConfig;

  placedObjects: FindItPlaceable[] = [];
  collectedObjects: placeable.PlaceableBase[] = [];
  collectedRedHerrings: placeable.PlaceableBase[] = [];
  stylesheets: { [key: string]: { [key: string]: PIXI.Spritesheet } } = {};
  levelTypeImages: { [key: string]: { [key: string]: string } } = {};
  totalCount = 0;
  collectedCount = 0;
  searchPosition: [number, number] = [0, 0];
  startTime = Date.now();
  textureToken = pixiUtil.createLoadingToken();
  zoomFilter: BulgePinchFilter = new BulgePinchFilter({
    radius: this.zoomSize,
    strength: 0.5,
    center: [0.5, 0.5],
  });

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;
  CollisionBorderType = CollisionBorderType;

  clearPlayState(): void {
    this.infoPlaceableList = [];
    this.levelType = '';
    this.placedObjects = [];
    this.collectedObjects = [];
    this.collectedRedHerrings = [];
    this.totalCount = 0;
    this.collectedCount = 0;
    this.startTime = Date.now();
  }

  get playStateResult(): PlayStateResult {
    const rate =
      this.collectedCount === this.totalCount
        ? 2
        : this.collectedCount > (this.totalCount / 3) * 2
        ? 1
        : 0; // Math.floor((this.collectedCount / this.totalCount) * 2)
    return {
      stars: rate,
      time: Date.now() - this.startTime,
      collected: this.collectedCount,
      total: this.totalCount,
      classified: 0,
      correctClassified: 0,
      itemList: this.collectedObjects,
      redHerringList: this.collectedRedHerrings,
    };
  }

  get escalationLevelObject(): FindItPlaceable[][] {
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

  get searchSize(): number {
    return this.gameWidth / 6;
  }

  get zoomSize(): number {
    return this.searchSize; // (this.searchSize / 5) * 4;
  }

  getDisplayColliderScaleFactor(placeable: FindItPlaceable): number {
    const gameContainer = this.$refs.gameContainer as GameContainer;
    const config =
      gameConfig[this.levelType].categories[placeable.type].settings;
    if (gameContainer && config.explanationText) {
      const absolutePosition = [
        (placeable.position[0] / 100) * gameContainer.backgroundTextureSize[0],
        (placeable.position[1] / 100) * gameContainer.backgroundTextureSize[1],
      ];
      const delta = [
        absolutePosition[0] - this.absoluteSearchPosition[0],
        absolutePosition[1] - this.absoluteSearchPosition[1],
      ];
      if (
        Math.abs(delta[0]) < this.searchSize &&
        Math.abs(delta[1]) < this.searchSize
      ) {
        return 2;
      }
    }
    return 1;
  }

  getDisplayPosition(placeable: FindItPlaceable): [number, number] {
    const gameContainer = this.$refs.gameContainer as GameContainer;
    const config =
      gameConfig[this.levelType].categories[placeable.type].settings;
    if (gameContainer && config.explanationText) {
      const boarder = [
        this.absoluteSearchPosition[0] > 0 &&
        this.absoluteSearchPosition[0] < this.searchSize
          ? -1
          : this.absoluteSearchPosition[0] <
              gameContainer.backgroundTextureSize[0] &&
            this.absoluteSearchPosition[0] >
              gameContainer.backgroundTextureSize[0] - this.searchSize
          ? 1
          : 0,
        this.absoluteSearchPosition[1] > 0 &&
        this.absoluteSearchPosition[1] < this.searchSize
          ? -1
          : this.absoluteSearchPosition[1] <
              gameContainer.backgroundTextureSize[1] &&
            this.absoluteSearchPosition[1] >
              gameContainer.backgroundTextureSize[1] - this.searchSize
          ? 1
          : 0,
      ];
      const isAtBoarder = [boarder[0] !== 0, boarder[1] !== 0];
      if (isAtBoarder[0] || isAtBoarder[1]) {
        const absolutePosition = [
          (placeable.position[0] / 100) *
            gameContainer.backgroundTextureSize[0],
          (placeable.position[1] / 100) *
            gameContainer.backgroundTextureSize[1],
        ];
        const delta = [
          absolutePosition[0] - this.absoluteSearchPosition[0],
          absolutePosition[1] - this.absoluteSearchPosition[1],
        ];
        if (
          Math.abs(delta[0]) < this.searchSize &&
          Math.abs(delta[1]) < this.searchSize &&
          (absolutePosition[0] < this.searchSize ||
            absolutePosition[0] >
              gameContainer.backgroundTextureSize[0] - this.searchSize ||
            absolutePosition[1] < this.searchSize ||
            absolutePosition[1] >
              gameContainer.backgroundTextureSize[1] - this.searchSize)
        ) {
          const deltaRelative = [
            isAtBoarder[0]
              ? placeable.position[0] - this.relativeSearchPosition[0]
              : 0,
            isAtBoarder[1]
              ? placeable.position[1] - this.relativeSearchPosition[1]
              : 0,
          ];
          const transformConsumption = (
            value: number,
            delta: number,
            sign: number
          ): number => {
            if (value !== 0 && Math.sign(delta) === sign) {
              return Math.sin(value * (Math.PI / 2)) * 4;
            }
            return 0;
          };
          return [
            placeable.position[0] -
              transformConsumption(
                isAtBoarder[0] ? delta[0] / this.searchSize : 0,
                deltaRelative[0],
                boarder[0]
              ),
            placeable.position[1] -
              transformConsumption(
                isAtBoarder[1] ? delta[1] / this.searchSize : 0,
                deltaRelative[1],
                boarder[1]
              ),
          ];
        }
      }
    }
    return placeable.position;
  }

  mounted(): void {
    tutorialService.registerGetList(this.updateTutorial, this.authHeaderTyp);
    setTimeout(() => {
      this.calculateRelativeSearchPosition();
    }, 1000);
  }

  updateTutorial(steps: Tutorial[]): void {
    this.tutorialSteps = steps.filter((step) => step.type === tutorialType);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTutorial);
  }

  get gameConfigTypes(): string[] {
    return configParameter.getGameConfigTypes(
      gameConfig as any,
      this.levelType
    );
  }

  unmounted(): void {
    this.deregisterAll();
    pixiUtil.cleanupToken(this.textureToken);
  }

  getSearchMask(placeable: FindItPlaceable): any {
    const config =
      gameConfig[this.levelType].categories[placeable.type].settings;
    if (!config.explanationText) {
      return this.$refs.searchMask;
    }
    return null;
  }

  drawSearchObject(graphics: PIXI.Graphics): void {
    graphics.clear();
    graphics.lineStyle(10, '#ff0000');
    graphics.drawCircle(0, 0, this.searchSize);
  }

  searchGraphics!: PIXI.Graphics;
  drawSearchMask(graphics: PIXI.Graphics): void {
    this.searchGraphics = graphics;
    this.updatedSearchMask();
  }

  updatedSearchMask(): void {
    if (this.searchGraphics && this.searchGraphics.geometry) {
      this.searchGraphics.clear();
      if (this.hasNoneClickableObjects) {
        this.searchGraphics.beginFill('#ffffff');
        const searchDelta = this.searchSize * 2;
        this.searchGraphics.drawRect(
          -searchDelta,
          -searchDelta,
          this.gameWidth + searchDelta * 2,
          this.gameHeight + searchDelta * 2
        );
        this.searchGraphics.beginHole();
        this.searchGraphics.drawCircle(
          this.searchPosition[0],
          this.searchPosition[1],
          this.searchSize
        );
        this.searchGraphics.endHole();
        this.searchGraphics.endFill();
      }
    }
    this.updateZoomFilter();
  }

  updateZoomFilter(): void {
    this.zoomFilter.center = [
      this.searchPosition[0] / this.gameWidth,
      this.searchPosition[1] / this.gameHeight,
    ];
  }

  absoluteSearchPosition: [number, number] = [0, 0];
  relativeSearchPosition: [number, number] = [50, 50];
  calculateRelativeSearchPosition(): void {
    const gameContainer = this.$refs.gameContainer as GameContainer;
    if (gameContainer) {
      this.absoluteSearchPosition = [
        this.searchPosition[0] +
          gameContainer.gameObjectOffsetRelativeToScreenMax[0] +
          gameContainer.gameObjectOffsetRelativeToScreen[0],
        this.searchPosition[1] +
          gameContainer.gameObjectOffsetRelativeToScreenMax[1] +
          gameContainer.gameObjectOffsetRelativeToScreen[1],
      ];
      this.relativeSearchPosition = [
        (this.absoluteSearchPosition[0] /
          gameContainer.backgroundTextureSize[0]) *
          100,
        (this.absoluteSearchPosition[1] /
          gameContainer.backgroundTextureSize[1]) *
          100,
      ];
    }
  }

  offsetChanged(): void {
    this.updateZoomFilter();
    this.calculateRelativeSearchPosition();
  }

  onSearchPositionChanged(searchPosition: [number, number]): void {
    this.searchPosition = searchPosition;
    this.updatedSearchMask();
    this.calculateRelativeSearchPosition();
  }

  @Watch('gameHeight', { immediate: true })
  onGameWidthSet(): void {
    this.zoomFilter.radius = this.zoomSize;
    this.searchPosition = [this.gameWidth / 2, this.gameHeight / 2];
  }

  previousLevelType = '';
  isReadyForPlay = false;
  @Watch('level', { immediate: true })
  async onLevelChanged(): Promise<void> {
    if (this.level) {
      this.isReadyForPlay = false;
      this.placedObjects = [];
      const levelType = this.level.parameter.type
        ? this.level.parameter.type
        : configParameter.getDefaultLevelType(gameConfig as any);
      if (this.levelType !== levelType) {
        this.levelType = levelType;
      }
      const items = configParameter.getItemsForLevel(
        gameConfig as any,
        this.level
      );
      const spriteSheetTypes = items
        .map((item) => item.type)
        .filter((value, index, array) => array.indexOf(value) === index);
      for (const spriteSheetType of spriteSheetTypes) {
        await until(() => this.spriteSheetLoaded(spriteSheetType));
      }
      this.placedObjects = items
        .filter((item) => this.hasTexture(item.type, item.name))
        .map((item) =>
          convertToFindItPlaceable(
            item,
            gameConfig[levelType],
            this.getTexture(item.type, item.name)
          )
        );
      this.previousLevelType = levelType;
      this.totalCount = this.collectableObjects.length;
      this.collectedObjects = [];
      this.collectedRedHerrings = [];
      this.collectedCount = 0;
      this.infoPlaceableList = [];
      this.startTime = Date.now();
      this.playStateType = PlayStateType.play;
      this.isReadyForPlay = true;
    }
  }

  @Watch('levelType', { immediate: true })
  onLevelTypeChanged(): void {
    if (this.levelType || !this.stylesheets[this.levelType]) {
      this.stylesheets[this.levelType] = {};
      for (const typeName of this.gameConfigTypes) {
        const settings =
          gameConfig[this.levelType].categories[typeName].settings;
        setTimeout(() => {
          if (
            settings &&
            settings.spritesheet &&
            this.previousLevelType !== this.levelType
          ) {
            pixiUtil
              .loadTexture(settings.spritesheet, this.textureToken)
              .then((sheet) => {
                this.stylesheets[this.levelType][typeName] = sheet;
                this.levelTypeImages[typeName] = {};
                pixiUtil.convertSpritesheetToBase64(
                  sheet,
                  this.levelTypeImages[typeName]
                );
              });
          }
        }, 100);
      }
    }
  }

  getSpriteSheetForType(objectType: string): PIXI.Spritesheet | undefined {
    if (this.stylesheets[this.levelType])
      return this.stylesheets[this.levelType][objectType];
    return undefined;
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
    if (spriteSheet) return pixiUtil.getSpriteAspect(spriteSheet, objectName);
    return 1;
  }

  get collectableObjects(): FindItPlaceable[] {
    return this.placedObjects.filter(
      (obj) =>
        gameConfig[this.levelType].categories[obj.type].settings.collectable
    );
  }

  get noneCollectableObjects(): FindItPlaceable[] {
    return this.placedObjects.filter(
      (obj) =>
        !gameConfig[this.levelType].categories[obj.type].settings.collectable
    );
  }

  get hasNoneClickableObjects(): boolean {
    return (
      this.placedObjects.filter(
        (obj) =>
          !gameConfig[this.levelType].categories[obj.type].settings
            .explanationText
      ).length > 0
    );
  }

  @Watch('placedObjects.length', { immediate: false })
  onPlaceablesCountChanged(): void {
    if (
      this.isReadyForPlay &&
      this.totalCount > 0 &&
      this.collectableObjects.length === 0
    ) {
      this.$emit('playFinished', this.playStateResult);
      this.playStateType = PlayStateType.win;
    }
  }

  get clickedPlaceableConfigSettings(): any {
    if (this.infoPlaceableList.length > 0) {
      return gameConfig[this.levelType].categories[
        this.infoPlaceableList[0].type
      ].settings;
    }
    return null;
  }

  get clickedPlaceableConfig(): any {
    if (this.infoPlaceableList.length > 0) {
      return gameConfig[this.levelType].categories[
        this.infoPlaceableList[0].type
      ].items[this.infoPlaceableList[0].name];
    }
    return null;
  }

  getCollectable(placeable: FindItPlaceable): boolean {
    const config =
      gameConfig[this.levelType].categories[placeable.type].settings;
    return config.collectable;
  }

  saveTutorialStep = false;
  infoPlaceableList: FindItPlaceable[] = [];
  async destroyPlaceable(
    value: FindItPlaceable,
    event: PointerEvent
  ): Promise<void> {
    const config = gameConfig[this.levelType].categories[value.type].settings;
    if (config.explanationText) {
      if (
        !this.infoPlaceableList.find(
          (item) => item.type === value.type && item.name === value.name
        )
      )
        this.infoPlaceableList.push(value);
    }
    const placeableConfig =
      gameConfig[this.levelType].categories[value.type].items[value.name];
    const index = this.placedObjects.indexOf(value);
    if (config.collectable) {
      if (index > -1) {
        this.collectedObjects.push(placeable.convertToBase(value));
        this.placedObjects.splice(index, 1);
      }
      this.collectedCount++;
    } else if (placeableConfig.explanationKey) {
      if (index > -1) {
        this.collectedRedHerrings.push(placeable.convertToBase(value));
        this.placedObjects.splice(index, 1);
      }
    }
    if (placeableConfig.explanationKey) {
      const tutorialStepName = `${value.type}-${placeableConfig.explanationKey}`;
      event.preventDefault();
      this.showToolbox = true;
      await until(() => !this.saveTutorialStep);
      this.saveTutorialStep = true;
      if (!this.tutorialSteps.find((item) => item.step === tutorialStepName)) {
        const tutorialItem: Tutorial = {
          step: tutorialStepName,
          type: tutorialType,
          order: 0,
        };
        await tutorialService.addTutorialStep(
          tutorialItem,
          this.authHeaderTyp,
          this.eventBus
        );
        this.saveTutorialStep = false;
      } /*else {
        this.saveTutorialStep = false;
        setTimeout(() => {
          this.showToolbox = false;
        }, 2000);
      }*/
    }
  }

  @Watch('showToolbox', { immediate: true })
  onShowToolboxChanged(): void {
    if (!this.showToolbox) {
      this.infoPlaceableList.splice(0, 1);
      if (this.infoPlaceableList.length > 0) this.showToolbox = true;
    }
  }

  gameObjectClick(objectList: GameObject[], event: PointerEvent): void {
    for (const obj of objectList) {
      const placeable = obj.source as FindItPlaceable;
      if (placeable) this.destroyPlaceable(placeable, event);
    }
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
  }

  handleEscalation(placeable: FindItPlaceable): void {
    placeable.escalationStepIndex++;
    if (placeable.escalationStepIndex >= placeable.escalationSteps.length) {
      ElMessage({
        message: this.$t('module.playing.findit.participant.lost'),
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
    placeable: FindItPlaceable
  ): PIXIParticles.EmitterConfigV3 | null {
    const typeConfig =
      gameConfig[this.levelType].categories[placeable.type].settings;
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

.clickedPlaceable {
  align-items: center;
  width: 100%;
  display: flex;
  padding: 2rem;

  img {
    margin: auto;
  }
}

.collected-right {
  font-size: var(--font-size-xxlarge);
  font-weight: var(--font-weight-bold);
  color: var(--color-green);
}

.collected-wrong {
  font-size: var(--font-size-xxlarge);
  font-weight: var(--font-weight-bold);
  color: var(--color-red);
}
</style>
