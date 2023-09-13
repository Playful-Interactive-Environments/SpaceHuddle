<template>
  <div
    class="gameArea"
    v-if="levelType"
    :style="{ height: height }"
    v-loading="isSaving"
  >
    <GameContainer
      ref="gameContainer"
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :background-texture="gameConfig[levelType].settings.background"
      :background-movement="BackgroundMovement.Pan"
      :detect-collision="false"
      :use-gravity="false"
      :use-borders="false"
      :activatedObjectOnRegister="true"
      @click="placeObject"
      v-model:selectedObject="selectedObject"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <GameObject
            v-for="placeable in renderList"
            :key="placeable.uuid"
            v-model:id="placeable.id"
            :type="placeable.shape"
            :collider-delta="20"
            :show-bounds="false"
            :object-space="ObjectSpace.RelativeToBackground"
            v-model:x="placeable.position[0]"
            v-model:y="placeable.position[1]"
            v-model:rotation="placeable.rotation"
            v-model:scale="placeable.scale"
            :options="{
              name: placeable.name,
              collisionFilter: {
                group: -1,
              },
            }"
            :source="placeable"
            :use-physic="false"
            @positionChanged="placeablePositionChanged"
          >
            <CustomSprite
              :texture="placeable.texture"
              :anchor="0.5"
              :width="placeable.width"
              :aspect-ration="getObjectAspect(placeable.type, placeable.name)"
              :object-space="ObjectSpace.RelativeToBackground"
              :outline="
                selectedObject && selectedObject.id === placeable.id
                  ? 0xff0000
                  : null
              "
            >
            </CustomSprite>
          </GameObject>
          <!--<Graphics
            @render="drawSelectedBorder"
            :x="selectionMaskX"
            :y="selectionMaskY"
            :rotation="selectionMaskRotation"
            :scale="selectionMaskScale"
          ></Graphics>-->
        </container>
      </template>
    </GameContainer>
    <!--<div class="overlay-top">
      <div @click="showToolbox = true">
        <font-awesome-icon icon="screwdriver-wrench" />
      </div>
      <div v-if="placedObjects.length > 5" @click="showLevelSettings = true">
        <font-awesome-icon icon="save" />
      </div>
    </div>-->
    <div class="overlay-selected-item">
      <div @click="showOptions = !showOptions">...</div>
      <div
        v-if="selectedObject && showOptions"
        @click="scaleSelectedObject(-0.2)"
      >
        <font-awesome-icon icon="square-minus" />
      </div>
      <div
        v-if="selectedObject && showOptions"
        @click="scaleSelectedObject(0.2)"
      >
        <font-awesome-icon icon="square-plus" />
      </div>
      <div
        v-if="selectedObject && showOptions"
        @click="rotateSelectedObject(-10)"
      >
        <font-awesome-icon icon="rotate-left" />
      </div>
      <div
        v-if="selectedObject && showOptions"
        @click="rotateSelectedObject(10)"
      >
        <font-awesome-icon icon="rotate-right" />
      </div>
      <!--<div v-if="selectedObject && showOptions" @click="showToolbox = true">
        <font-awesome-icon icon="screwdriver-wrench" />
      </div>-->
      <div v-if="selectedObject && showOptions" @click="deleteSelectedObject">
        <font-awesome-icon icon="trash" />
      </div>
      <div
        v-if="!level && showOptions && placedObjects.length > 5"
        @click="showLevelSettings = true"
      >
        <font-awesome-icon icon="save" />
      </div>
      <div
        v-else-if="level && showOptions && placedObjects.length > 5"
        @click="saveLevel"
      >
        <font-awesome-icon icon="save" />
      </div>
      <div
        v-if="
          level &&
          showOptions &&
          authHeaderTyp === EndpointAuthorisationType.MODERATOR
        "
        @click="approveLevel"
      >
        <font-awesome-icon icon="thumbs-up" />
      </div>
    </div>
    <DrawerBottomOverlay
      v-if="levelType"
      v-model="showToolbox"
      :title="
        $t('module.information.findit.participant.itemSelection.selectItem')
      "
    >
      <el-space wrap>
        <el-button
          v-for="objectType of gameConfigTypes"
          :key="objectType"
          type="primary"
          size="large"
          @click="objectTypeClicked(objectType)"
          :class="{ active: objectType === activeObjectType }"
        >
          <font-awesome-icon
            :icon="gameConfig[levelType][objectType].settings.icon"
          />
        </el-button>
      </el-space>
      <el-space wrap>
        <div v-for="objectName of ObjectsForActiveType" :key="objectName">
          <SpriteCanvas
            :texture="getActiveTexture(objectName)"
            :aspect-ration="getObjectAspect(activeObjectType, objectName)"
            class="placeable"
            :class="{ selected: activeObjectName === objectName }"
            :tint="
              placementState[objectName].currentCount <
              placementState[objectName].maxCount
                ? '#ffffff'
                : inactiveColor
            "
            :background-color="
              placementState[objectName].currentCount <
              placementState[objectName].maxCount
                ? backgroundColor
                : inactiveColor
            "
            @pointerdown="objectNameClicked(objectName)"
          />
          <br />
          {{ placementState[objectName].currentCount }} /
          {{ placementState[objectName].maxCount }}
        </div>
      </el-space>
    </DrawerBottomOverlay>
  </div>
  <LevelSettings
    v-model:show-modal="showLevelSettings"
    @saveLevel="saveLevel"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as PIXI from 'pixi.js';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer, {
  BackgroundMovement,
} from '@/components/shared/atoms/game/GameContainer.vue';
import * as placeable from '@/modules/information/coolit/types/Placeable';
import { v4 as uuidv4 } from 'uuid';
import * as pixiUtil from '@/utils/pixi';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import LevelSettings from '@/modules/information/coolit/organisms/LevelSettings.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ElMessage } from 'element-plus';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import CustomSprite from '@/components/shared/atoms/game/CustomSprite.vue';
import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import DrawerBottomOverlay from '@/components/participant/molecules/DrawerBottomOverlay.vue';
import * as themeColors from '@/utils/themeColors';
import { Idea } from '@/types/api/Idea';
import gameConfig from '@/modules/information/coolit/data/gameConfig.json';
import { until } from '@/utils/wait';
import * as configParameter from '@/modules/information/coolit/utils/configParameter';
import { LevelWorkflowType } from '@/modules/information/coolit/types/LevelWorkflowType';

// The current state of the edit mode
export interface BuildState {
  maxCount: number;
  currentCount: number;
}

export interface BuildStateResult {
  time: number;
  total: number;
  categoryCount: { [key: string]: number };
  itemList: placeable.PlaceableBase[];
}

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpace;
    },
    BackgroundMovement() {
      return BackgroundMovement;
    },
  },
  components: {
    SpriteCanvas,
    CustomSprite,
    FontAwesomeIcon,
    LevelSettings,
    GameObject,
    GameContainer,
    DrawerBottomOverlay,
  },
  emits: ['editFinished', 'update:levelType', 'approved'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ForestFireEdit extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly levelType!: string;
  @Prop({ default: EndpointAuthorisationType.PARTICIPANT })
  authHeaderTyp!: EndpointAuthorisationType;
  @Prop({ default: null }) readonly level!: Idea | null;
  @Prop({ default: '100%' }) readonly height!: string;
  activeObjectType = '';
  activeObjectName = '';
  showToolbox = false;
  gameWidth = 0;
  gameHeight = 0;
  showOptions = false;

  placedObjects: placeable.Placeable[] = [];
  placementState: { [key: string]: BuildState } = {};
  stylesheets: { [key: string]: PIXI.Spritesheet } = {};
  showLevelSettings = false;
  selectedObject: GameObject | null = null;
  startTime = Date.now();
  gameConfig = gameConfig;
  isSaving = false;

  EndpointAuthorisationType = EndpointAuthorisationType;

  get inactiveColor(): string {
    return themeColors.getInactiveColor();
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  get buildResult(): BuildStateResult {
    const categoryCount: { [key: string]: number } = {};
    for (const categoryName of Object.keys(gameConfig[this.levelType])) {
      categoryCount[categoryName] = this.placedObjects.filter(
        (item) => item.type === categoryName
      ).length;
    }
    return {
      time: Date.now() - this.startTime,
      total: this.placedObjects.length,
      categoryCount: categoryCount,
      itemList: this.placedObjects.map((item) => placeable.convertToBase(item)),
    };
  }

  get ObjectsForActiveType(): string[] {
    if (this.activeObjectType) {
      const list = Object.keys(
        gameConfig[this.levelType][this.activeObjectType]
      );
      return list.filter((name) => name !== 'settings');
    }
    return [];
  }

  getSpriteSheetForType(objectType: string): PIXI.Spritesheet {
    return this.stylesheets[objectType];
  }

  getActiveTexture(objectName: string): PIXI.Texture | string {
    if (this.activeObjectType) {
      const spriteSheet = this.getSpriteSheetForType(this.activeObjectType);
      if (spriteSheet) return spriteSheet.textures[objectName];
    }
    return '';
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

  getValues(objectName: string): { count: number; max: number } {
    const config =
      gameConfig[this.levelType][this.activeObjectType][objectName];
    return {
      count: 0,
      max: config.maxCount,
    };
  }

  selectionMaskGraphic: PIXI.Graphics | null = null;
  drawSelectedBorder(graphics: PIXI.Graphics): void {
    this.selectionMaskGraphic = graphics;
    this.updateSelectionMask();
  }

  get selectionMaskX(): number {
    if (this.selectedObject) return this.selectedObject.displayX;
    return 0;
  }

  get selectionMaskY(): number {
    if (this.selectedObject) return this.selectedObject.displayY;
    return 0;
  }

  get selectionMaskRotation(): number {
    if (this.selectedObject) return this.selectedObject.rotationValue;
    return 0;
  }

  get selectionMaskScale(): number {
    if (this.selectedObject) return this.selectedObject.scale;
    return 0;
  }

  updateSelectionMask(): void {
    if (this.selectedObject && this.selectionMaskGraphic) {
      const width = this.selectedObject.displayWidth;
      const height = this.selectedObject.displayHeight;

      if (this.selectedObject.type === 'rect') {
        this.selectionMaskGraphic.clear();
        this.selectionMaskGraphic.lineStyle(2, '#ff0000');
        this.selectionMaskGraphic.drawRect(
          -width / 2,
          -height / 2,
          width,
          height
        );
      } else {
        this.selectionMaskGraphic.clear();
        this.selectionMaskGraphic.lineStyle(2, '#ff0000');
        this.selectionMaskGraphic.drawCircle(
          0,
          0,
          (width > height ? width : height) / 2
        );
      }
    } else if (this.selectionMaskGraphic) this.selectionMaskGraphic.clear();
  }

  get gameConfigTypes(): string[] {
    return configParameter.getGameConfigTypes(this.levelType);
  }

  unmounted(): void {
    for (const typeName of this.gameConfigTypes) {
      const settings = gameConfig[this.levelType][typeName].settings;
      PIXI.Assets.unload(settings.spritesheet);
    }
  }

  @Watch('levelType', { immediate: true })
  onLevelTypeChanged(): void {
    console.log(this.levelType, gameConfig);
    if (this.levelType) {
      this.activeObjectType = gameConfig[this.levelType].settings.defaultType;
      this.activeObjectName = gameConfig[this.levelType].settings.defaultName;
      for (const typeName of this.gameConfigTypes) {
        const settings = gameConfig[this.levelType][typeName].settings;
        setTimeout(() => {
          if (settings && settings.spritesheet && !this.stylesheets[typeName]) {
            PIXI.Assets.load(settings.spritesheet).then((sheet) => {
              this.stylesheets[typeName] = sheet;
            });
          }
        }, 100);
        for (const objectName in gameConfig[this.levelType][typeName]) {
          if (objectName !== 'settings') {
            const hazardParameter =
              gameConfig[this.levelType][typeName][objectName];
            this.placementState[objectName] = {
              maxCount: hazardParameter.maxCount,
              currentCount: 0,
            };
          }
        }
      }
    }
  }

  previousLevelType = '';
  @Watch('level', { immediate: true })
  async onLevelChanged(): Promise<void> {
    this.showToolbox = false;
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
      this.$emit('update:levelType', levelType);
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
    }
  }

  getObjectAspect(objectType: string, objectName: string): number {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    return pixiUtil.getSpriteAspect(spriteSheet, objectName);
  }

  isObjectCollectable(objectType: string): boolean {
    return gameConfig[this.levelType][objectType].settings.collectable;
  }

  async saveLevel(name: string): Promise<void> {
    this.isSaving = true;
    if (!this.level) {
      const idea = await ideaService.postIdea(
        this.taskId,
        {
          keywords: name,
          parameter: {
            state: LevelWorkflowType.created,
            type: this.levelType,
            items: this.renderList.map((obj) => {
              return placeable.convertToBase(obj);
            }),
          },
        },
        this.authHeaderTyp
      );
      this.$emit('editFinished', idea.id, this.buildResult);
    } else {
      this.level.parameter = {
        state: this.level.parameter.state
          ? this.level.parameter.state
          : LevelWorkflowType.created,
        type: this.levelType,
        items: this.renderList.map((obj) => {
          return placeable.convertToBase(obj);
        }),
      };
      const idea = await ideaService.putIdea(this.level, this.authHeaderTyp);
      this.$emit('editFinished', idea.id, this.buildResult);
    }
    this.isSaving = false;
  }

  approveLevel(): void {
    if (this.level) {
      this.isSaving = true;
      this.level.parameter = {
        state: LevelWorkflowType.approved,
        type: this.levelType,
        items: this.renderList.map((obj) => {
          return placeable.convertToBase(obj);
        }),
      };
      ideaService.putIdea(this.level, this.authHeaderTyp);
      this.$emit('approved');
      this.isSaving = false;
    }
  }

  clickPosition: [number, number] = [0, 0];
  placeObject(event: any): void {
    this.clickPosition = [
      event.relativeMousePositionToBackground.x,
      event.relativeMousePositionToBackground.y,
    ];
    this.showToolbox = true;
  }

  createObject(position: [number, number]): void {
    setTimeout(() => {
      if (this.activeObjectType) {
        const configParameter =
          gameConfig[this.levelType][this.activeObjectType][
            this.activeObjectName
          ];
        if (
          this.placementState[this.activeObjectName].currentCount ===
          this.placementState[this.activeObjectName].maxCount
        ) {
          ElMessage({
            message: this.$t(
              'module.information.coolit.participant.maxCountPlaced'
            ),
            type: 'error',
            center: true,
            showClose: true,
          });
          return;
        }
        this.placementState[this.activeObjectName].currentCount++;
        const texture = this.getActiveTexture(
          this.activeObjectName
        ) as PIXI.Texture<PIXI.Resource>;
        position = this.ensurePositionVisibility(
          texture,
          configParameter.width,
          position
        );
        const placeable: placeable.Placeable = {
          uuid: uuidv4(),
          id: 0,
          type: this.activeObjectType,
          name: this.activeObjectName,
          texture: texture,
          width: configParameter.width,
          shape: configParameter.shape,
          position: position,
          rotation: 0,
          scale: 1,
          escalationSteps: [],
          escalationStepIndex: 0,
        };
        this.placedObjects.push(placeable);
      }
    }, 100);
  }

  private ensurePositionVisibility(
    texture: PIXI.Texture<PIXI.Resource>,
    width: number,
    position: [number, number]
  ): [number, number] {
    if (
      this.$refs.gameContainer &&
      this.isObjectCollectable(this.activeObjectType)
    ) {
      const container = this.$refs.gameContainer as GameContainer;
      const aspect = (texture as any).orig.width / (texture as any).orig.height;
      const aspectContainer = container.getBackgroundAspect();
      const height = (width / aspect) * aspectContainer;
      if (position[0] < width / 2) position[0] = width / 2;
      if (position[1] < height / 2) position[1] = height / 2;
      if (position[0] > 100 - width / 2) position[0] = 100 - width / 2;
      if (position[1] > 100 - height / 2) position[1] = 100 - height / 2;
    }
    return position;
  }

  placeablePositionChanged(position: [number, number]): void {
    if (this.selectedObject) {
      const selectionId = this.selectedObject.source.id;
      const selectionObject = this.placedObjects.find(
        (item) => item.id === selectionId
      );
      if (selectionObject) {
        const texture = this.getTexture(
          this.selectedObject.source.type,
          this.selectedObject.source.name
        ) as PIXI.Texture<PIXI.Resource>;
        const configParameter =
          gameConfig[this.levelType][this.selectedObject.source.type][
            this.selectedObject.source.name
          ];
        const previousPosition = [...position];
        const newPosition = this.ensurePositionVisibility(
          texture,
          configParameter.width,
          position
        );
        if (
          newPosition[0] !== previousPosition[0] ||
          newPosition[1] !== previousPosition[1]
        ) {
          this.selectedObject.updatePosition([...newPosition]);
        }
      }
    }
  }

  @Watch('selectedObject', { immediate: true })
  onSelectedObjectChanged(): void {
    if (this.selectedObject) this.updateSelectionMask();
  }

  deleteSelectedObject(): void {
    const selected = this.selectedObject?.source;
    const index = this.placedObjects.indexOf(selected);
    if (selected && index >= 0) {
      this.selectionMaskGraphic = null;
      this.selectedObject = null;
      this.placementState[selected.name].currentCount--;
      this.placedObjects.splice(index, 1);
    }
  }

  rotateSelectedObject(angle: number): void {
    if (this.selectedObject) {
      let newValue = this.selectedObject.source.rotation + angle;
      if (newValue < 0) newValue += 360;
      if (newValue > 360) newValue -= 360;
      this.selectedObject.source.rotation = newValue;
    }
  }

  scaleSelectedObject(value: number): void {
    if (this.selectedObject) {
      const newValue = this.selectedObject.source.scale + value;
      if (newValue > 0.5 && newValue <= 3)
        this.selectedObject.source.scale = newValue;
    }
  }

  objectTypeClicked(objectType: string): void {
    this.activeObjectType = objectType;
  }

  objectNameClicked(objectName: string): void {
    this.showToolbox = false;
    this.activeObjectName = objectName;
    this.createObject(this.clickPosition);
  }

  get renderList(): placeable.Placeable[] {
    const getSortNumber = (placeable: placeable.Placeable): number => {
      return gameConfig[this.levelType][placeable.type].settings.order;
    };
    return this.placedObjects.sort(
      (a, b) => getSortNumber(a) - getSortNumber(b)
    );
  }
}
</script>

<style scoped lang="scss">
.gameArea {
  position: relative;
  height: calc(100%);
  width: 100%;
}

.custom-renderer-wrapper {
  height: 100%;
}

.overlay-top {
  z-index: 100;
  position: absolute;
  top: 1rem;
  right: 1rem;
  font-size: var(--font-size-xxxlarge);
  color: white;
}

.overlay-selected-item {
  z-index: 100;
  position: absolute;
  top: 1rem;
  left: 1rem;
  text-align: center;
  background-color: var(--color-primary);
  border-radius: 5px;
  color: white;

  .sliderInside {
    position: absolute;
    top: 30px;
    left: 30px;
  }

  .deleteObject {
    z-index: 200;
    width: 1rem;
    height: 1rem;
    text-align: center;
    position: absolute;
    top: calc(70px - 0.5rem);
    left: calc(70px - 0.5rem);
  }

  div {
    padding: 0.5rem;
  }
}

.placeable {
  border-radius: var(--border-radius);
}

.selected {
  //border: red 2px solid;
  border-radius: var(--border-radius);
}

.el-space {
  display: flex;
}

.el-space + .el-space {
  padding-top: 1rem;
}

.active {
  background-color: var(--color-informing);
}
</style>
