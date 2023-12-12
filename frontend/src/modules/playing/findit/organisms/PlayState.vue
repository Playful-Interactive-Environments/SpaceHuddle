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
      :collision-borders="CollisionBorderType.Screen"
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
              :saturation="placeable.saturation"
            >
            </CustomSprite>
            <custom-particle-container
              v-if="placeable.escalationStepIndex > 0"
              :config="getConfigForPlaceable(placeable)"
              :parentEventBus="eventBus"
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
            v-if="gameWidth && noneCollectableObjects.length > 0"
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
    <h2 class="heading heading--medium">
      {{ $t('module.playing.findit.participant.win') }}
    </h2>
    <p>
      {{ $t('module.playing.findit.participant.winText') }}
    </p>
    <div class="endObjects">
      <div
        v-for="object in endObjects"
        :key="object.name"
        :id="object.name"
        class="endObject"
        @click="activeObjectChanged(object, object.name, true)"
      >
        <SpriteCanvas
          :texture="getTexture(object.type, object.name)"
          :aspect-ration="getObjectAspect(object.type, object.name)"
          :width="(gameWidth - 50) / 2"
          :height="((gameHeight / 10) * 4) / 2"
          :background-color="backgroundColor"
          class="endObjectSprites"
        />
      </div>
    </div>
    <h2 class="heading heading--medium" v-if="this.activeObject !== null">
      {{ $t('module.playing.findit.participant.placeables.'+ levelType + '.' + this.activeObject.type + '.' + getExplanationKey(this.activeObject) + '.name') }}
    </h2>
    <div class="infoText">
      <p class="marginTop" v-if="this.activeObject !== null">
        {{
          $t(
            'module.playing.findit.participant.endCardTexts.' +
              getExplanationKey(this.activeObject)
          )
        }}
      </p>
    </div>
    <el-button
      class="el-button--submit returnButton"
      @click="this.$emit('playFinished', this.playStateResult)"
    >
      {{ $t('module.playing.findit.participant.returnToMenu') }}
    </el-button>
  </div>
  <div
    class="gameArea result"
    :style="{ height: height }"
    v-if="playStateType === PlayStateType.lost"
  >
    <h2 class="heading heading--medium">
      {{ $t('module.playing.findit.participant.lost') }}
    </h2>
    <p>
      {{ $t('module.playing.findit.participant.lostText') }}
    </p>
    <div class="endObjects">
      <div
        v-for="object in endObjects"
        :key="object.name"
        :id="object.name"
        class="endObject"
        @click="activeObjectChanged(object, object.name, true)"
      >
        <SpriteCanvas
          :texture="getTexture(object.type, object.name)"
          :aspect-ration="getObjectAspect(object.type, object.name)"
          :width="(gameWidth - 50) / 2"
          :height="((gameHeight / 10) * 4) / 2"
          :background-color="backgroundColor"
          class="endObjectSprites"
        />
      </div>
    </div>
    <div class="infoText">
      <p class="marginTop" v-show="this.activeObjectId !== ''">
        {{
          $t(
            'module.playing.findit.participant.endCardTexts.' +
              this.activeObjectId
          )
        }}
      </p>
    </div>
    <el-button
      class="el-button--submit returnButton"
      @click="this.$emit('playFinished', this.playStateResult)"
    >
      {{ $t('module.playing.findit.participant.returnToMenu') }}
    </el-button>
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
  CollisionBorderType,
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
import * as escalationConfig from '@/modules/playing/findit/data/escalation.json';
import CustomParticleContainer from '@/components/shared/atoms/game/CustomParticleContainer.vue';
import * as themeColors from '@/utils/themeColors';
import gameConfig from '@/modules/playing/findit/data/gameConfig.json';
import { Idea } from '@/types/api/Idea';
import * as configParameter from '@/utils/game/configParameter';

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
  itemList: placeable.PlaceableBase[];
}

interface FindItPlaceable extends placeable.Placeable {
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
    uuid: result.uuid,
    id: result.id,
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
  };
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
  clickedPlaceable: FindItPlaceable | null = null;
  levelType = '';
  gameConfig = gameConfig;

  placedObjects: FindItPlaceable[] = [];
  collectedObjects: placeable.PlaceableBase[] = [];
  stylesheets: { [key: string]: PIXI.Spritesheet } = {};
  totalCount = 0;
  collectedCount = 0;
  searchPosition: [number, number] = [0, 0];
  startTime = Date.now();

  playStateType = PlayStateType.play;
  PlayStateType = PlayStateType;
  CollisionBorderType = CollisionBorderType;

  endObjects: placeable.PlaceableBase[] = [];
  activeObjectId = '';
  activeObject: placeable.PlaceableBase | null = null;

  clearPlayState(): void {
    this.clickedPlaceable = null;
    this.levelType = '';
    this.placedObjects = [];
    this.collectedObjects = [];
    this.endObjects = [];
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
    return configParameter.getGameConfigTypes(
      gameConfig as any,
      this.levelType
    );
  }

  unmounted(): void {
    this.deregisterAll();
    for (const typeName of this.gameConfigTypes) {
      const settings = gameConfig[this.levelType].categories[typeName].settings;
      pixiUtil.unloadTexture(settings.spritesheet);
    }
  }

  getSearchMask(placeable: FindItPlaceable): any {
    const config =
      gameConfig[this.levelType].categories[placeable.type].settings;
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
    if (this.searchGraphics && this.searchGraphics.geometry) {
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
  isReadyForPlay = false;
  @Watch('level', { immediate: true })
  async onLevelChanged(): Promise<void> {
    if (this.level) {
      this.isReadyForPlay = false;
      this.placedObjects = [];
      const levelType = this.level.parameter.type
        ? this.level.parameter.type
        : configParameter.getDefaultLevelType(gameConfig as any);
      if (this.previousLevelType && this.previousLevelType !== levelType) {
        const gameConfigTypes = Object.keys(gameConfig[levelType].categories);
        for (const typeName of gameConfigTypes) {
          const previousSettings =
            gameConfig[this.previousLevelType].categories[typeName].settings;
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
      this.collectedCount = 0;
      this.clickedPlaceable = null;
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
          gameConfig[this.levelType].categories[typeName].settings;
        setTimeout(() => {
          if (
            settings &&
            settings.spritesheet &&
            this.previousLevelType !== this.levelType
          ) {
            pixiUtil
              .loadTexture(settings.spritesheet, this.eventBus)
              .then((sheet) => {
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

  @Watch('placedObjects.length', { immediate: false })
  onPlaceablesCountChanged(): void {
    if (
      this.isReadyForPlay &&
      this.totalCount > 0 &&
      this.collectableObjects.length === 0
    ) {
      //this.$emit('playFinished', this.playStateResult);
      this.endObjects = this.getEndObjects();
      this.playStateType = PlayStateType.win;
    }
  }

  getEndObjects(): placeable.PlaceableBase[] {
    const uniqueNamesSet = new Set();
    return this.collectedObjects.filter((obj) => {
      if (!uniqueNamesSet.has(obj.name)) {
        uniqueNamesSet.add(obj.name);
        return true;
      }
      return false;
    });
  }

  get clickedPlaceableConfigSettings(): any {
    if (this.clickedPlaceable) {
      return gameConfig[this.levelType].categories[this.clickedPlaceable.type]
        .settings;
    }
    return null;
  }

  get clickedPlaceableConfig(): any {
    if (this.clickedPlaceable) {
      return gameConfig[this.levelType].categories[this.clickedPlaceable.type][
        this.clickedPlaceable.name
      ];
    }
    return null;
  }

  getCollectable(placeable: FindItPlaceable): boolean {
    const config =
      gameConfig[this.levelType].categories[placeable.type].settings;
    return config.collectable;
  }

  destroyPlaceable(value: FindItPlaceable, event: PointerEvent): void {
    const config = gameConfig[this.levelType].categories[value.type].settings;
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
    const placeableConfig =
      gameConfig[this.levelType].categories[value.type].items[value.name];
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

  handleEscalation(placeable: FindItPlaceable): void {
    placeable.escalationStepIndex++;
    if (placeable.escalationStepIndex >= placeable.escalationSteps.length) {
      ElMessage({
        message: this.$t('module.playing.findit.participant.lost'),
        type: 'error',
        center: true,
        showClose: true,
        onClose: () => {
          //this.$emit('playFinished', this.playStateResult);
          this.endObjects = this.getEndObjects();
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

  activeObjectChanged(object, id, scroll = false) {
    let element = document.getElementById(this.activeObjectId);
    if (element) {
      element.classList.remove('objectContainerActive');
    }
    this.activeObjectId = id;
    this.activeObject = object;
    element = document.getElementById(id);
    if (element) {
      element.classList.add('objectContainerActive');
      if (scroll) {
        element.scrollIntoView({
          behavior: 'smooth',
          block: 'center',
          inline: 'center',
        });
      }
    }
  }

  getExplanationKey(object: placeable.PlaceableBase): string {
    if (object.name === 'man' || object.name === 'woman') {
      return 'person';
    } else if (object.name.substring(0, 6) === 'bottle') {
      return 'bottle';
    } else {
      return object.name;
    }
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

.result {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-start;
  font-size: var(--font-size-default);
  text-align: center;
  padding-top: 2rem;
}

.endObjects {
  position: relative;
  display: flex;
  justify-content: flex-start;
  align-items: center;
  height: 32%;
  width: 100%;
  z-index: 10;
  overflow-x: scroll;
  overflow-y: hidden;
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
  margin: 2rem 0;
  background-color: var(--color-brown-xlight);
  outline: 0.5rem solid var(--color-brown);
}

.endObject {
  position: relative;
  margin: 1rem;
  transition: 0.3s;
  padding: 0.5rem;
  border: 0.3rem solid var(--color-brown);
  background-color: var(--color-background);
  border-radius: var(--border-radius-small);
}

.objectContainerActive {
  z-index: 2;
  transform: translateY(-1rem);
  transition: 0.3s;
}

.endObjectSprites {
  pointer-events: none;
}

.marginTop {
  margin-top: 1rem;
  padding: 0 1rem;
}

.returnButton {
  position: absolute;
  bottom: 2rem;
}

.infoText {
  height: 2rem;
  transition: 0.3s;
}
</style>
