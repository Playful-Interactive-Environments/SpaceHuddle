<template>
  <div class="gameArea">
    <GameContainer
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="false"
      :use-gravity="false"
      :background-texture="gameConfig.settings.background"
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

const tutorialType = 'find-it-object';

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
  @Prop({ default: [] }) readonly levelData!: placeable.PlaceableBase[];
  @Prop({ default: {} }) readonly gameConfig!: any;
  renderer!: PIXI.Renderer;
  gameWidth = 0;
  gameHeight = 0;
  showToolbox = false;
  tutorialSteps: Tutorial[] = [];
  clickedPlaceable: placeable.Placeable | null = null;

  placedObjects: placeable.Placeable[] = [];
  collectedObjects: placeable.PlaceableBase[] = [];
  stylesheets: { [key: string]: PIXI.Spritesheet } = {};
  totalCount = 0;
  collectedCount = 0;
  searchPosition: [number, number] = [0, 0];
  startTime = Date.now();

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
    for (const typeName in this.gameConfig) {
      const settings = this.gameConfig[typeName].settings;
      if (settings) {
        PIXI.Assets.load(settings.spritesheet).then(
          (sheet) => (this.stylesheets[typeName] = sheet)
        );
      }
    }
    tutorialService.registerGetList(
      this.updateTutorial,
      EndpointAuthorisationType.PARTICIPANT
    );

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
    for (const typeName in this.gameConfig) {
      const settings = this.gameConfig[typeName].settings;
      if (settings) PIXI.Assets.unload(settings.spritesheet);
    }
  }

  getSearchMask(placeable: placeable.Placeable): any {
    const config = this.gameConfig[placeable.type].settings;
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

  @Watch('levelData', { immediate: true })
  async onLevelDataChanged(): Promise<void> {
    for (const value of Object.values(this.levelData)) {
      if (!value.type) value.type = this.gameConfig.settings.defaultType;
      await until(() => this.spriteSheetLoaded(value.type));
      if (this.hasTexture(value.type, value.name)) {
        this.placedObjects.push(
          placeable.convertToDetailData(
            value,
            this.gameConfig,
            this.getTexture(value.type, value.name)
          )
        );
      }
    }
    this.totalCount = this.collectableObjects.length;
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
      (obj) => this.gameConfig[obj.type].settings.collectable
    );
  }

  get noneCollectableObjects(): placeable.Placeable[] {
    return this.placedObjects.filter(
      (obj) => !this.gameConfig[obj.type].settings.collectable
    );
  }

  @Watch('placedObjects.length', { immediate: false })
  onPlaceablesCountChanged(): void {
    if (this.totalCount > 0 && this.collectableObjects.length === 0) {
      this.$emit('playFinished', this.playStateResult);
    }
  }

  get clickedPlaceableConfigSettings(): any {
    if (this.clickedPlaceable) {
      return this.gameConfig[this.clickedPlaceable.type].settings;
    }
    return null;
  }

  get clickedPlaceableConfig(): any {
    if (this.clickedPlaceable) {
      return this.gameConfig[this.clickedPlaceable.type][
        this.clickedPlaceable.name
      ];
    }
    return null;
  }

  getCollectable(placeable: placeable.Placeable): boolean {
    const config = this.gameConfig[placeable.type].settings;
    return config.collectable;
  }

  destroyPlaceable(value: placeable.Placeable): void {
    const config = this.gameConfig[value.type].settings;
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
    const placeableConfig = this.gameConfig[value.type][value.name];
    if (placeableConfig.explanationKey) {
      const tutorialStepName = `${value.type}-${placeableConfig.explanationKey}`;
      if (!this.tutorialSteps.find((item) => item.step === tutorialStepName)) {
        this.showToolbox = true;
        const tutorialItem: Tutorial = {
          step: tutorialStepName,
          type: tutorialType,
          order: 0,
        };
        tutorialService.addTutorialStep(
          tutorialItem,
          EndpointAuthorisationType.PARTICIPANT,
          this.eventBus
        );
      }
    }
  }

  gameObjectClick(objectList: GameObject[]): void {
    for (const obj of objectList) {
      if (obj.source) this.destroyPlaceable(obj.source);
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
        onClose: () => this.$emit('playFinished', this.playStateResult),
      });
    }
  }

  getConfigForPlaceable(
    placeable: placeable.Placeable
  ): PIXIParticles.EmitterConfigV3 | null {
    const typeConfig = this.gameConfig[placeable.type].settings;
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
</style>
