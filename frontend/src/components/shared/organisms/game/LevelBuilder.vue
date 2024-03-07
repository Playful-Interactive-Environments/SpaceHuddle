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
      :collision-borders="CollisionBorderType.None"
      :activatedObjectOnRegister="true"
      :waitForDataLoad="waitForDataLoad"
      @click="placeObject"
      v-model:selectedObject="selectedObject"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <GameObject
            v-for="placeable in renderList"
            :key="placeable.uuid"
            :shape="placeable.shape"
            :polygon-shape="placeable.polygonShape"
            :collider-delta="colliderDelta"
            :show-bounds="false"
            :anchor="placeable.pivot"
            :object-space="ObjectSpace.RelativeToBackground"
            :posX="placeable.position[0]"
            :posY="placeable.position[1]"
            :angle="placeable.rotation"
            :scale="placeable.scale"
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
            <SpriteConverter
              :texture="placeable.texture"
              :anchor="placeable.pivot"
              :space-width="placeable.width"
              :aspect-ration="getObjectAspect(placeable.type, placeable.name)"
              :object-space="ObjectSpace.RelativeToBackground"
              :outline="
                selectedObject && selectedObject === placeable.gameObject
                  ? 0xff0000
                  : null
              "
              :saturation="placeable.saturation"
              :pre-render-filters="
                !selectedObject || selectedObject !== placeable.gameObject
              "
            >
            </SpriteConverter>
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
      <div v-if="isReadyForSave" @click="showLevelSettings = true">
        <font-awesome-icon icon="save" />
      </div>
    </div>-->
    <div class="overlay-selected-item">
      <div @click="showOptions = !showOptions">...</div>
      <div
        v-if="selectedObject && showOptions && canScale"
        @click="scaleSelectedObject(-0.2)"
      >
        <font-awesome-icon icon="square-minus" />
      </div>
      <div
        v-if="selectedObject && showOptions && canScale"
        @click="scaleSelectedObject(0.2)"
      >
        <font-awesome-icon icon="square-plus" />
      </div>
      <div
        v-if="selectedObject && showOptions && canRotate"
        @click="rotateSelectedObject(-10)"
      >
        <font-awesome-icon icon="rotate-left" />
      </div>
      <div
        v-if="selectedObject && showOptions && canRotate"
        @click="rotateSelectedObject(10)"
      >
        <font-awesome-icon icon="rotate-right" />
      </div>
      <div
        v-if="selectedObject && showOptions && canChangeSaturation"
        @click="changeSaturation(0.1)"
      >
        <font-awesome-icon icon="circle-half-stroke" />
        <font-awesome-icon icon="plus" class="iconInfo" />
      </div>
      <div
        v-if="selectedObject && showOptions && canChangeSaturation"
        @click="changeSaturation(-0.1)"
      >
        <font-awesome-icon icon="circle-half-stroke" />
        <font-awesome-icon icon="minus" class="iconInfo" />
      </div>
      <!--<div v-if="selectedObject && showOptions" @click="showToolbox = true">
        <font-awesome-icon icon="screwdriver-wrench" />
      </div>-->
      <div v-if="selectedObject && showOptions" @click="deleteSelectedObject">
        <font-awesome-icon icon="trash" />
      </div>
      <div
        v-if="!level && showOptions && isReadyForSave"
        @click="showLevelSettings = true"
      >
        <font-awesome-icon icon="save" />
      </div>
      <div
        v-else-if="level && showOptions && isReadyForSave"
        @click="saveCurrentLevel"
      >
        <font-awesome-icon icon="save" />
      </div>
      <div
        v-if="
          level &&
          showOptions &&
          authHeaderTyp === EndpointAuthorisationType.MODERATOR &&
          canApprove
        "
        @click="approveLevel"
      >
        <font-awesome-icon icon="thumbs-up" />
      </div>
      <div
        v-if="
          level &&
          showOptions &&
          authHeaderTyp === EndpointAuthorisationType.MODERATOR &&
          canExport
        "
        @click="copyToClipboard"
      >
        <font-awesome-icon icon="copy" />
      </div>
      <div
        v-if="
          level &&
          showOptions &&
          authHeaderTyp === EndpointAuthorisationType.MODERATOR &&
          canExport
        "
        @click="pasteFromClipboard"
      >
        <font-awesome-icon icon="paste" />
      </div>
    </div>
    <div
      v-if="canChangeLayer"
      class="overlay-layers"
      :style="{
        'max-width': 'calc(100% / 2)',
        'max-height': 'calc(100% - 2rem)',
      }"
    >
      <div class="minimize" @click="showLayers = !showLayers">...</div>
      <el-scrollbar v-if="showLayers" height="150px">
        <draggable v-model="placedObjects" item-key="uuid">
          <template v-slot:item="{ element }">
            <div
              :style="{
                color: element.gameObject === selectedObject ? 'red' : 'white',
              }"
            >
              {{ element.name }}
            </div>
          </template>
        </draggable>
      </el-scrollbar>
    </div>
    <DrawerBottomOverlay
      v-if="levelType"
      v-model="showToolbox"
      :title="$t('shared.organism.game.levelBuilder.itemSelection.selectItem')"
    >
      <el-space wrap>
        <el-button
          v-for="objectType of regionGameConfigTypes"
          :key="objectType"
          type="primary"
          size="large"
          @click="objectTypeClicked(objectType)"
          :class="{ active: objectType === activeObjectType }"
        >
          <font-awesome-icon
            :icon="gameConfig[levelType].categories[objectType].settings.icon"
          />
        </el-button>
      </el-space>
      <el-space wrap>
        <div
          class="clickable placeable-info"
          v-for="objectName of RegionObjectsForActiveType"
          :key="objectName"
          @mousedown="objectNameClicked(objectName)"
        >
          <div :class="{ selected: activeObjectName === objectName }">
            <div
              class="placeable"
              :style="{
                '--alpha':
                  placementState[objectName].currentCount <
                  placementState[objectName].maxCount
                    ? 1
                    : 0.5,
                '--placeable-color':
                  placementState[objectName].currentCount <
                  placementState[objectName].maxCount
                    ? '#ffffff00'
                    : inactiveColor,
              }"
            >
              <img
                :src="levelTypeImages[activeObjectType][objectName]"
                :alt="objectName"
              />
            </div>
          </div>
          {{ placementState[objectName].currentCount }} /
          {{ placementState[objectName].maxCount }}
        </div>
      </el-space>
    </DrawerBottomOverlay>
  </div>
  <LevelSettings
    v-model:show-modal="showLevelSettings"
    @saveLevel="saveCurrentLevel"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as PIXI from 'pixi.js';
import GameObject, {
  IGameObjectSource,
} from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer, {
  BackgroundMovement,
  CollisionBorderType,
} from '@/components/shared/atoms/game/GameContainer.vue';
import * as placeable from '@/types/game/Placeable';
import { v4 as uuidv4 } from 'uuid';
import * as pixiUtil from '@/utils/pixi';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import LevelSettings from '@/components/shared/organisms/game/LevelSettings.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ElMessage, ElMessageBox } from 'element-plus';
import { ObjectSpaceType } from '@/types/enum/ObjectSpaceType';
import DrawerBottomOverlay from '@/components/participant/molecules/DrawerBottomOverlay.vue';
import * as themeColors from '@/utils/themeColors';
import { Idea } from '@/types/api/Idea';
import { until } from '@/utils/wait';
import * as configParameter from '@/utils/game/configParameter';
import { LevelWorkflowType } from '@/types/game/LevelWorkflowType';
import { copyToClipboard, pasteFromClipboard } from '@/utils/date';
import * as polygon from '@/utils/polygon';
import draggable from 'vuedraggable';

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

export function getLevelType(
  level: Idea,
  gameConfig: placeable.PlaceableConfig
): string {
  return level.parameter.type
    ? level.parameter.type
    : configParameter.getDefaultLevelType(gameConfig);
}

interface LevelPlaceable extends placeable.Placeable, IGameObjectSource {}

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpaceType;
    },
    BackgroundMovement() {
      return BackgroundMovement;
    },
  },
  components: {
    FontAwesomeIcon,
    LevelSettings,
    DrawerBottomOverlay,
    draggable,
  },
  emits: ['editFinished', 'update:levelType', 'approved'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class LevelBuilder extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly levelType!: string;
  @Prop({ default: EndpointAuthorisationType.PARTICIPANT })
  authHeaderTyp!: EndpointAuthorisationType;
  @Prop({ default: null }) readonly level!: Idea | null;
  @Prop({ default: '100%' }) readonly height!: string;
  @Prop({ default: true }) readonly canRotate!: boolean;
  @Prop({ default: true }) readonly canScale!: boolean;
  @Prop({ default: false }) readonly canChangeSaturation!: boolean;
  @Prop({ default: false }) readonly canChangeLayer!: boolean;
  @Prop({ default: false }) readonly canApprove!: boolean;
  @Prop({ default: false }) readonly canExport!: boolean;
  @Prop() readonly gameConfig!: placeable.PlaceableConfig;
  @Prop({ default: 0 }) readonly colliderDelta!: number;
  @Prop({ default: null }) readonly customSortOrder!:
    | ((placeable: LevelPlaceable) => number)
    | null;
  @Prop({ default: null }) readonly customScaleFactor!:
    | ((placeable: LevelPlaceable) => number)
    | null;
  @Prop({ default: null }) readonly customSaturation!:
    | ((placeable: LevelPlaceable) => number)
    | null;
  activeObjectType = '';
  activeObjectName = '';
  showToolbox = false;
  gameWidth = 0;
  gameHeight = 0;
  showOptions = false;
  showLayers = false;

  placedObjects: LevelPlaceable[] = [];
  placementState: { [key: string]: BuildState } = {};
  stylesheets: { [key: string]: PIXI.Spritesheet } = {};
  levelTypeImages: { [key: string]: { [key: string]: string } } = {};
  showLevelSettings = false;
  selectedObject: GameObject | null = null;
  startTime = Date.now();
  isSaving = false;
  savedItems: placeable.PlaceableBase[] = [];
  waitForDataLoad = true;
  textureToken = pixiUtil.createLoadingToken();

  EndpointAuthorisationType = EndpointAuthorisationType;
  CollisionBorderType = CollisionBorderType;

  get isReadyForSave(): boolean {
    return this.placedObjects.length > 5;
  }

  get inactiveColor(): string {
    return themeColors.getInactiveColor();
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  get buildResult(): BuildStateResult {
    const categoryCount: { [key: string]: number } = {};
    for (const categoryName of Object.keys(
      this.gameConfig[this.levelType].categories
    )) {
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
      return Object.keys(
        this.gameConfig[this.levelType].categories[this.activeObjectType].items
      );
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
      this.gameConfig[this.levelType].categories[this.activeObjectType].items[
        objectName
      ];
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
    if (this.selectedObject) return this.selectedObject.rotation;
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

      if (this.selectedObject.shape === 'rect') {
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
    return configParameter.getGameConfigTypes(this.gameConfig, this.levelType);
  }

  get regionGameConfigTypes(): string[] {
    const typeNameList = this.gameConfigTypes;
    const result: string[] = [];
    for (const typeName of typeNameList) {
      const settings =
        this.gameConfig[this.levelType].categories[typeName].settings;
      if (settings.placingRegions) {
        for (const region of settings.placingRegions) {
          if (
            polygon.containsPoint(
              region,
              this.clickPosition[0],
              this.clickPosition[1]
            )
          ) {
            result.push(typeName);
            break;
          }
        }
      } else {
        result.push(typeName);
      }
    }
    return result;
  }

  get RegionObjectsForActiveType(): string[] {
    const itemNameList = this.ObjectsForActiveType;
    const result: string[] = [];
    for (const itemName of itemNameList) {
      const settings =
        this.gameConfig[this.levelType].categories[this.activeObjectType].items[
          itemName
        ];
      if (settings.placingRegions) {
        for (const region of settings.placingRegions) {
          if (
            polygon.containsPoint(
              region,
              this.clickPosition[0],
              this.clickPosition[1]
            )
          ) {
            result.push(itemName);
            break;
          }
        }
      } else {
        result.push(itemName);
      }
    }
    return result;
  }

  cacheSavedItems(): void {
    this.savedItems = this.renderList.map((obj) => {
      return placeable.convertToBase(obj);
    });
  }

  saveChanges(): void {
    const sceneItems = this.renderList.map((obj) => {
      const saveObj = placeable.convertToBase(obj);
      if (obj.gameObject) {
        saveObj.position = obj.gameObject.inputPosition;
        saveObj.rotation = obj.gameObject.inputAngle;
        saveObj.scale = obj.gameObject.inputScale;
      }
      return saveObj;
    });
    let hasChanges = sceneItems.length !== this.savedItems.length;
    if (!hasChanges) {
      for (let i = 0; i < sceneItems.length; i++) {
        if (
          sceneItems[i].name !== this.savedItems[i].name ||
          sceneItems[i].position[0] !== this.savedItems[i].position[0] ||
          sceneItems[i].position[1] !== this.savedItems[i].position[1] ||
          sceneItems[i].rotation !== this.savedItems[i].rotation ||
          sceneItems[i].scale !== this.savedItems[i].scale
        ) {
          hasChanges = true;
          break;
        }
      }
    }
    if (hasChanges && this.isReadyForSave) {
      const sceneLevel = this.loadedLevel ? { ...this.loadedLevel } : null;
      const sceneLevelType = this.levelType;
      ElMessageBox.confirm(
        (this as any).$t('shared.organism.game.levelBuilder.save.text'),
        (this as any).$t('shared.organism.game.levelBuilder.save.header'),
        {
          confirmButtonText: (this as any).$t(
            'shared.organism.game.levelBuilder.save.yes'
          ),
          cancelButtonText: (this as any).$t(
            'shared.organism.game.levelBuilder.save.no'
          ),
          type: 'warning',
        }
      )
        .then(() => {
          this.saveLevel(sceneLevel, sceneLevelType, sceneItems);
        })
        .catch(() => {
          //
        });
    }
  }

  unmounted(): void {
    this.saveChanges();
    pixiUtil.cleanupToken(this.textureToken);
  }

  @Watch('levelType', { immediate: true })
  onLevelTypeChanged(): void {
    if (this.levelType) {
      this.activeObjectType =
        this.gameConfig[this.levelType].settings.defaultType;
      this.activeObjectName =
        this.gameConfig[this.levelType].settings.defaultName;
      const sheetImageLoaded: { [key: string]: boolean } = {};
      for (const typeName of this.gameConfigTypes) {
        sheetImageLoaded[typeName] = false;
        const settings =
          this.gameConfig[this.levelType].categories[typeName].settings;
        setTimeout(() => {
          if (settings && settings.spritesheet && !this.stylesheets[typeName]) {
            pixiUtil
              .loadTexture(settings.spritesheet, this.textureToken)
              .then((sheet) => {
                this.stylesheets[typeName] = sheet;
                this.levelTypeImages[typeName] = {};
                pixiUtil
                  .convertSpritesheetToBase64(
                    sheet,
                    this.levelTypeImages[typeName]
                  )
                  .then(() => (sheetImageLoaded[typeName] = true));
              });
          }
        }, 100);
        for (const objectName in this.gameConfig[this.levelType].categories[
          typeName
        ].items) {
          const hazardParameter =
            this.gameConfig[this.levelType].categories[typeName].items[
              objectName
            ];
          this.placementState[objectName] = {
            maxCount: hazardParameter.maxCount,
            currentCount: 0,
          };
        }
      }
      until(() => !Object.values(sheetImageLoaded).includes(false)).then(() => {
        this.waitForDataLoad = false;
      });
    }
  }

  loadedLevelType = '';
  loadedLevel: Idea | null = null;
  @Watch('level', { immediate: true })
  async onLevelChanged(): Promise<void> {
    this.showToolbox = false;
    if (this.level) {
      this.saveChanges();
      this.placedObjects = [];
      const levelType = getLevelType(this.level, this.gameConfig);
      this.$emit('update:levelType', levelType);
      const items = configParameter.getItemsForLevel(
        this.gameConfig,
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
          placeable.convertToGameSourceData(
            item,
            this.gameConfig[levelType],
            this.getTexture(item.type, item.name)
          )
        );
      this.loadedLevelType = levelType;
      this.loadedLevel = this.level;
      this.cacheSavedItems();
    }
  }

  getObjectAspect(objectType: string, objectName: string): number {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    return pixiUtil.getSpriteAspect(spriteSheet, objectName);
  }

  async saveCurrentLevel(name: string): Promise<void> {
    this.isSaving = true;
    const items = this.renderList.map((obj) => {
      const saveObj = placeable.convertToBase(obj);
      if (obj.gameObject) {
        saveObj.position = obj.gameObject.inputPosition;
        saveObj.rotation = obj.gameObject.inputAngle;
        saveObj.scale = obj.gameObject.inputScale;
      }
      return saveObj;
    });
    const idea = await this.saveLevel(this.level, this.levelType, items, name);
    this.$emit('editFinished', idea.id, this.buildResult);
    this.savedItems = items;
    this.isSaving = false;
  }

  async saveLevel(
    level: Idea | null,
    levelType: string,
    items: placeable.PlaceableBase[],
    name = 'temp'
  ): Promise<Idea> {
    if (!level) {
      return await ideaService.postIdea(
        this.taskId,
        {
          keywords: name,
          parameter: {
            state: LevelWorkflowType.created,
            type: levelType,
            items: items,
          },
        },
        this.authHeaderTyp
      );
    } else {
      if (!level.parameter) level.parameter = {};
      if (!level.parameter.state)
        level.parameter.state = LevelWorkflowType.created;
      level.parameter.type = levelType;
      level.parameter.items = items;
      return await ideaService.putIdea(level, this.authHeaderTyp);
    }
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
    const typeNameList = this.regionGameConfigTypes;
    if (typeNameList.length > 0) {
      //this.activeObjectType = typeNameList[0];
      this.showToolbox = true;
    }
  }

  createObject(position: [number, number]): void {
    setTimeout(() => {
      if (this.activeObjectType) {
        const configParameter =
          this.gameConfig[this.levelType].categories[this.activeObjectType]
            .items[this.activeObjectName];
        if (
          this.placementState[this.activeObjectName].currentCount ===
          this.placementState[this.activeObjectName].maxCount
        ) {
          ElMessage({
            message: this.$t(
              'shared.organism.game.levelBuilder.maxCountPlaced'
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
          this.activeObjectType,
          this.activeObjectName,
          texture,
          configParameter.width,
          position
        );
        const placeable: LevelPlaceable = {
          gameObject: null,
          uuid: uuidv4(),
          type: this.activeObjectType,
          name: this.activeObjectName,
          texture: texture,
          width: configParameter.width,
          shape: configParameter.shape,
          polygonShape: configParameter.polygonShape,
          pivot: configParameter.pivot ?? [0.5, 0.5],
          position: position,
          rotation: 0,
          scale: 1,
          placingRegions: configParameter.placingRegions,
          saturation: 1,
        };
        this.placedObjects.push(placeable);
      }
    }, 100);
  }

  private ensurePositionVisibility(
    configType: string,
    configName: string,
    texture: PIXI.Texture<PIXI.Resource>,
    width: number,
    position: [number, number]
  ): [number, number] {
    let placingRegionList =
      this.gameConfig[this.levelType].categories[configType].items[configName]
        .placingRegions;
    if (!placingRegionList)
      placingRegionList =
        this.gameConfig[this.levelType].categories[configType].settings
          .placingRegions;
    if (this.$refs.gameContainer && !!placingRegionList) {
      const container = this.$refs.gameContainer as GameContainer;
      const aspect = (texture as any).orig.width / (texture as any).orig.height;
      const aspectContainer = container.getBackgroundAspect();
      const height = (width / aspect) * aspectContainer;

      const getClosesPosition = (
        offsetAmount = 0,
        pivot: [number, number] = [0.5, 0.5]
      ): [number, number] => {
        if (!placingRegionList) return position;
        let closesPoint = {
          distance: Number.MAX_VALUE,
          point: [...position] as [number, number],
        };
        for (let region of placingRegionList) {
          if (offsetAmount > 0) {
            region = polygon.shrinkPolygon(region, -offsetAmount, [
              width * pivot[0] - width / 2,
              height * pivot[1] - height / 2,
            ]);
          }
          if (polygon.containsPoint(region, position[0], position[1])) {
            closesPoint = {
              distance: 0,
              point: [...position],
            };
            break;
          } else {
            const possiblePoint = polygon.closestPoint(
              region,
              position[0],
              position[1]
            );
            if (possiblePoint.distance < closesPoint.distance) {
              closesPoint = {
                distance: possiblePoint.distance,
                point: possiblePoint.point,
              };
            }
          }
        }
        return closesPoint.point;
      };

      const checkCompletelyInside =
        this.gameConfig[this.levelType].categories[configType].settings
          .checkCompletelyInside;
      if (checkCompletelyInside) {
        const configParameter =
          this.gameConfig[this.levelType].categories[configType].items[
            configName
          ];
        const pivot = configParameter.pivot ?? [0.5, 0.5];
        position[0] = getClosesPosition(width / 2, pivot)[0];
        position[1] = getClosesPosition(height / 2, pivot)[1];
        /*const placingRegion = placingRegionList[0];
        if (position[0] < placingRegion[0][0] + width * pivot[0])
          position[0] = placingRegion[0][0] + width * pivot[0];
        if (position[1] < placingRegion[0][1] + height * pivot[1])
          position[1] = placingRegion[0][1] + height * pivot[1];
        if (position[0] > placingRegion[2][0] - width * (1 - pivot[0]))
          position[0] = placingRegion[2][0] - width * (1 - pivot[0]);
        if (position[1] > placingRegion[2][1] - height * (1 - pivot[1]))
          position[1] = placingRegion[2][1] - height * (1 - pivot[1]);*/
      } else {
        const closesPoint = getClosesPosition();
        position[0] = closesPoint[0];
        position[1] = closesPoint[1];
      }
    }
    return position;
  }

  placeablePositionChanged(position: [number, number]): void {
    if (this.selectedObject && this.selectedObject.source) {
      const selectedSource = this.selectedObject.source as LevelPlaceable;
      const texture = this.getTexture(
        selectedSource.type,
        selectedSource.name
      ) as PIXI.Texture<PIXI.Resource>;
      const configParameter =
        this.gameConfig[this.levelType].categories[selectedSource.type].items[
          selectedSource.name
        ];
      const previousPosition = [...position];
      const newPosition = this.ensurePositionVisibility(
        selectedSource.type,
        selectedSource.name,
        texture,
        configParameter.width * selectedSource.scale,
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

  @Watch('selectedObject', { immediate: true })
  onSelectedObjectChanged(): void {
    if (this.selectedObject) this.updateSelectionMask();
  }

  deleteSelectedObject(): void {
    const selectedSource = this.selectedObject?.source as LevelPlaceable;
    const index = this.placedObjects.indexOf(selectedSource);
    if (selectedSource && index >= 0) {
      this.selectionMaskGraphic = null;
      this.selectedObject = null;
      this.placementState[selectedSource.name].currentCount--;
      this.placedObjects.splice(index, 1);
    }
  }

  rotateSelectedObject(angle: number): void {
    if (this.selectedObject && this.selectedObject.source) {
      const selectedSource = this.selectedObject.source as LevelPlaceable;
      let newValue = selectedSource.rotation + angle;
      if (newValue < 0) newValue += 360;
      if (newValue > 360) newValue -= 360;
      selectedSource.rotation = newValue;
    }
  }

  scaleSelectedObject(value: number): void {
    if (this.selectedObject && this.selectedObject.source) {
      const selectedSource = this.selectedObject.source as LevelPlaceable;
      const newValue = selectedSource.scale + value;
      if (newValue > 0.5 && newValue <= 3) {
        selectedSource.scale = newValue;
        this.placeablePositionChanged(selectedSource.position);
      }
    }
  }

  changeSaturation(value: number): void {
    if (this.selectedObject) {
      const selectedSource = this.selectedObject.source as LevelPlaceable;
      const newValue = selectedSource.saturation + value;
      if (newValue > 0.3 && newValue <= 1) {
        selectedSource.saturation = newValue;
      }
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

  get renderList(): LevelPlaceable[] {
    const getSortNumber = (placeable: LevelPlaceable): number => {
      if (this.customSortOrder) return this.customSortOrder(placeable);
      return this.gameConfig[this.levelType].categories[placeable.type].settings
        .order;
    };
    if (this.customScaleFactor || this.customSaturation) {
      for (const placeable of this.placedObjects) {
        if (this.customScaleFactor) {
          placeable.scale = this.customScaleFactor(placeable);
        }
        if (this.customSaturation) {
          placeable.saturation = this.customSaturation(placeable);
        }
      }
    }
    if (this.levelType) {
      return this.placedObjects.sort(
        (a, b) => getSortNumber(a) - getSortNumber(b)
      );
    }
    return this.placedObjects;
  }

  copyToClipboard(): void {
    const data = JSON.stringify({
      type: this.levelType,
      items: this.renderList.map((obj) => {
        return placeable.convertToBase(obj);
      }),
    });
    copyToClipboard(data, this.$t);
  }

  pasteFromClipboard(): void {
    pasteFromClipboard('text/plain', this.$t).then((textData) => {
      if (textData) {
        try {
          const data = JSON.parse(textData);
          if (data.type && data.items && data.type === this.levelType) {
            ElMessageBox.confirm(
              (this as any).$t('shared.organism.game.levelBuilder.paste.text'),
              (this as any).$t(
                'shared.organism.game.levelBuilder.paste.header'
              ),
              {
                confirmButtonText: (this as any).$t(
                  'shared.organism.game.levelBuilder.paste.yes'
                ),
                cancelButtonText: (this as any).$t(
                  'shared.organism.game.levelBuilder.paste.no'
                ),
                type: 'warning',
              }
            )
              .then(() => {
                this.placedObjects = [];
                const items = data.items as placeable.PlaceableBase[];
                this.placedObjects = items
                  .filter((item) => this.hasTexture(item.type, item.name))
                  .map((item) =>
                    placeable.convertToGameSourceData(
                      item,
                      this.gameConfig[this.levelType],
                      this.getTexture(item.type, item.name)
                    )
                  );
              })
              .catch(() => {
                //
              });
          } else {
            ElMessage({
              message: this.$t('confirm.clipboard.pasteNoData'),
              type: 'error',
              center: true,
              showClose: true,
            });
          }
        } catch (e) {
          ElMessage({
            message: this.$t('confirm.clipboard.pasteNoData'),
            type: 'error',
            center: true,
            showClose: true,
          });
        }
      }
    });
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

.overlay-layers {
  z-index: 100;
  position: absolute;
  top: 1rem;
  right: 1rem;
  font-size: var(--font-size-default);
  color: white;
  background-color: var(--color-primary);
  border-radius: 5px;
  padding: 0.5rem;
}

.placeable {
  border-radius: var(--border-radius);
}

.placeable {
  height: 5rem;
  width: 5rem;
  margin: 0.2rem;
  padding: 0.5rem;
  display: flex;
  background-color: var(--placeable-color);

  img {
    margin: auto;
    max-height: 4rem;
    opacity: var(--alpha);
  }
}

.clickable {
  cursor: pointer;
}

.placeable-info {
  text-align: center;
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

.iconInfo {
  position: absolute;
  font-size: var(--font-size-xxsmall);
}

.minimize {
  float: right;
}
</style>
