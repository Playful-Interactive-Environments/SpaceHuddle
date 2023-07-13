<template>
  <div class="gameArea">
    <GameContainer
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="false"
      :use-gravity="false"
      :use-borders="false"
      :activatedObjectOnRegister="true"
      @click="placeObject"
      v-model:selectedObject="selectedObject"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <sprite
            texture="/assets/games/forestfires/background/forest.jpg"
            :width="3 * gameHeight"
            :height="gameHeight"
          ></sprite>
          <GameObject
            v-for="placeable in renderList"
            :key="placeable.uuid"
            v-model:id="placeable.id"
            :type="placeable.shape"
            :object-space="ObjectSpace.Relative"
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
          >
            <CustomSprite
              :texture="placeable.texture"
              :anchor="0.5"
              :width="placeable.width"
              :aspect-ration="getObjectAspect(placeable.type, placeable.name)"
              :object-space="ObjectSpace.Relative"
              :tint="
                selectedObject && selectedObject.id === placeable.id
                  ? '#ff0000'
                  : '#ffffff'
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
    <div class="overlay-top">
      <div @click="showToolbox = true">
        <font-awesome-icon icon="screwdriver-wrench" />
      </div>
      <div v-if="placedObjects.length > 5" @click="showLevelSettings = true">
        <font-awesome-icon icon="save" />
      </div>
    </div>
    <div
      v-if="selectedObject"
      class="overlay-selected-item"
      :style="{
        '--x': `${selectedObject.position[0]}px`,
        '--y': `${selectedObject.position[1]}px`,
      }"
    >
      <round-slider
        v-model="selectedObject.source.rotation"
        max="360"
        start-angle="90"
        end-angle="+360"
        line-cap="round"
        radius="70"
        width="10"
        handleShape="dot"
        :show-tooltip="false"
      />
      <round-slider
        class="sliderInside"
        v-model="selectedObject.source.scale"
        min="0"
        max="2"
        step="0.01"
        start-angle="90"
        end-angle="+360"
        line-cap="round"
        radius="40"
        width="10"
        handleShape="dot"
        :show-tooltip="false"
      />
      <div class="deleteObject" @click="deleteSelectedObject">
        <font-awesome-icon icon="trash" />
      </div>
    </div>
  </div>
  <el-drawer
    v-model="showToolbox"
    :title="
      $t('module.information.forestfires.participant.itemSelection.selectItem')
    "
    :with-header="true"
  >
    <el-space wrap>
      <el-button
        v-for="objectType of Object.keys(gameConfig)"
        :key="objectType"
        type="primary"
        size="large"
        @click="objectTypeClicked(objectType)"
        :class="{ active: objectType === activeObjectType }"
      >
        <font-awesome-icon :icon="gameConfig[objectType].settings.icon" />
      </el-button>
    </el-space>
    <el-space wrap>
      <SpriteCanvas
        v-for="objectName of ObjectsForActiveType"
        :key="objectName"
        :texture="getTexture(objectName)"
        :aspect-ration="getObjectAspect(activeObjectType, objectName)"
        @click="objectNameClicked(objectName)"
      />
    </el-space>
  </el-drawer>
  <LevelSettings
    v-model:show-modal="showLevelSettings"
    @saveLevel="saveLevel"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Line } from 'vue-chartjs';
import { Application as Pixi } from 'vue3-pixi';
import * as PIXI from 'pixi.js';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import gameConfig from '@/modules/information/forestfires/data/gameConfig.json';
import Placeable from '@/modules/information/forestfires/types/Placeable';
import { v4 as uuidv4 } from 'uuid';
import { MouseConstraint } from 'matter-js';
import * as pixiUtil from '@/utils/pixi';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import LevelSettings from '@/modules/information/forestfires/organisms/LevelSettings.vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ElMessage } from 'element-plus';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import CustomSprite from '@/components/shared/atoms/game/CustomSprite.vue';
import RoundSlider from 'vue-three-round-slider/src';
import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';

// The current state of the edit mode
export interface PlacementState {
  maxCount: number;
  currentCount: number;
}

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpace;
    },
  },
  components: {
    SpriteCanvas,
    CustomSprite,
    FontAwesomeIcon,
    LevelSettings,
    GameObject,
    GameContainer,
    Line,
    Pixi,
    RoundSlider,
  },
  emits: ['editFinished'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ForestFireEdit extends Vue {
  @Prop() readonly taskId!: string;
  gameConfig = gameConfig;
  activeObjectType = 'hazards';
  activeObjectName = 'cigarette';
  showToolbox = false;
  gameWidth = 0;
  gameHeight = 0;

  placedObjects: Placeable[] = [];
  placementState: { [key: string]: PlacementState } = {};
  hazardSpritesheet!: PIXI.Spritesheet;
  obstacleSpritesheet!: PIXI.Spritesheet;
  showLevelSettings = false;
  selectedObject: GameObject | null = null;

  get ObjectsForActiveType(): string[] {
    const list = Object.keys(gameConfig[this.activeObjectType]);
    return list.filter((name) => name !== 'settings');
  }

  getTexture(objectName: string): PIXI.Texture | string {
    if (this.activeObjectType === 'hazards' && this.hazardSpritesheet)
      return this.hazardSpritesheet.textures[objectName];
    if (this.activeObjectType === 'obstacles' && this.obstacleSpritesheet)
      return this.obstacleSpritesheet.textures[objectName];
    return '/assets/games/forestfires/hazard.json';
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

  mounted(): void {
    PIXI.Assets.load('/assets/games/forestfires/hazard.json').then(
      (sheet) => (this.hazardSpritesheet = sheet)
    );
    PIXI.Assets.load('/assets/games/forestfires/obstacle.json').then(
      (sheet) => (this.obstacleSpritesheet = sheet)
    );

    // Set up hazard state counters
    for (const typeName in gameConfig) {
      for (const objectName in gameConfig[typeName]) {
        if (objectName !== 'settings') {
          const hazardParameter = gameConfig[typeName][objectName];
          this.placementState[objectName] = {
            maxCount: hazardParameter.maxCount,
            currentCount: 0,
          };
        }
      }
    }
  }

  getObjectAspect(objectType: string, objectName: string): number {
    if (objectType === 'hazards')
      return pixiUtil.getSpriteAspect(this.hazardSpritesheet, objectName);
    return pixiUtil.getSpriteAspect(this.obstacleSpritesheet, objectName);
  }

  saveLevel(name: string) {
    ideaService.postIdea(
      this.taskId,
      {
        keywords: name,
        parameter: this.placedObjects.map((obj) => {
          return {
            type: obj.type,
            name: obj.name,
            position: obj.position,
            rotation: obj.rotation,
            scale: obj.scale,
          };
        }),
      },
      EndpointAuthorisationType.PARTICIPANT
    );
    this.$emit('editFinished');
  }

  placeObject(event: MouseConstraint): void {
    setTimeout(() => {
      const configParameter =
        gameConfig[this.activeObjectType][this.activeObjectName];
      if (
        this.placementState[this.activeObjectName].currentCount ===
        this.placementState[this.activeObjectName].maxCount
      ) {
        ElMessage({
          message: this.$t(
            'module.information.forestfires.participant.maxCountPlaced'
          ),
          type: 'error',
          center: true,
          showClose: true,
        });
        return;
      }
      this.placementState[this.activeObjectName].currentCount++;
      const placeable: Placeable = {
        uuid: uuidv4(),
        id: 0,
        type: this.activeObjectType,
        name: this.activeObjectName,
        texture: this.getTexture(this.activeObjectName),
        width: configParameter.width,
        shape: configParameter.shape,
        position: [
          (event.mouse.position.x / this.gameWidth) * 100,
          (event.mouse.position.y / this.gameHeight) * 100,
        ],
        rotation: 0,
        scale: 1,
      };
      this.placedObjects.push(placeable);
    }, 100);
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

  objectTypeClicked(objectType: string): void {
    this.activeObjectType = objectType;
  }

  objectNameClicked(objectName: string): void {
    this.showToolbox = false;
    this.activeObjectName = objectName;
  }

  get renderList(): Placeable[] {
    const getSortNumber = (placeable: Placeable): number => {
      if (placeable.type === 'hazards') return 0;
      return 10;
    };
    return this.placedObjects.sort(
      (a, b) => getSortNumber(a) - getSortNumber(b)
    );
  }
}
</script>

<style scoped lang="scss">
.gameArea {
  --x: 0;
  --y: 0;
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
  //top: var(--x);
  //left: var(--y);
  top: 1rem;
  left: 1rem;
  text-align: center;

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
}
</style>
