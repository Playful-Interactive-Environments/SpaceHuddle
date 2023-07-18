<template>
  <div class="gameArea">
    <GameContainer
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="false"
      :use-gravity="false"
      background-texture="/assets/games/forestfires/background/forest.jpg"
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
            :source="placeable"
            :mask="getSearchMask(placeable)"
          >
            <CustomSprite
              :texture="placeable.texture"
              :anchor="0.5"
              :width="placeable.width"
              :aspect-ration="getObjectAspect(placeable.type, placeable.name)"
              :object-space="ObjectSpace.RelativeToBackground"
            >
            </CustomSprite>
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
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Line } from 'vue-chartjs';
import * as PIXI from 'pixi.js';
import { Prop, Watch } from 'vue-property-decorator';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer, {
  BackgroundPosition,
  BackgroundMovement,
} from '@/components/shared/atoms/game/GameContainer.vue';
import Placeable from '@/modules/information/forestfires/types/Placeable';
import * as pixiUtil from '@/utils/pixi';
import * as cashService from '@/services/cash-service';
import { Idea } from '@/types/api/Idea';
import * as authService from '@/services/auth-service';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import CustomSprite from '@/components/shared/atoms/game/CustomSprite.vue';
import { v4 as uuidv4 } from 'uuid';
import gameConfig from '@/modules/information/forestfires/data/gameConfig.json';
import { delay } from '@/utils/wait';

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
    Line,
  },
  emits: ['playFinished'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PlayState extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: [] }) readonly levelData!: Placeable[];
  renderer!: PIXI.Renderer;
  gameWidth = 0;
  gameHeight = 0;

  placedObjects: Placeable[] = [];
  hazardSpritesheet!: PIXI.Spritesheet;
  obstacleSpritesheet!: PIXI.Spritesheet;
  totalCount = 0;
  collectedCount = 0;
  searchPosition: [number, number] = [0, 0];

  mounted(): void {
    PIXI.Assets.load('/assets/games/forestfires/hazard.json').then(
      (sheet) => (this.hazardSpritesheet = sheet)
    );
    PIXI.Assets.load('/assets/games/forestfires/obstacle.json').then(
      (sheet) => (this.obstacleSpritesheet = sheet)
    );
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateIdeas);
  }

  getSearchMask(placeable: Placeable): any {
    const config = gameConfig[placeable.type].settings;
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
    await delay(200);
    for (const value of Object.values(this.levelData)) {
      if (!value.type) value.type = 'hazards';
      const configParameter = gameConfig[value.type][value.name];
      const placeable: Placeable = {
        uuid: uuidv4(),
        id: 0,
        type: value.type,
        name: value.name,
        texture: this.getTexture(value.type, value.name),
        width: configParameter.width,
        shape: configParameter.shape,
        position: value.position,
        rotation: value.rotation,
        scale: value.scale,
      };
      this.placedObjects.push(placeable);
    }
    this.totalCount = this.collectableObjects.length;
  }

  getSpriteSheetForType(objectType: string): PIXI.Spritesheet {
    if (objectType === 'hazards' && this.hazardSpritesheet)
      return this.hazardSpritesheet;
    if (objectType === 'obstacles' && this.obstacleSpritesheet)
      return this.obstacleSpritesheet;
    return this.hazardSpritesheet;
  }

  getTexture(objectType: string, objectName: string): PIXI.Texture | string {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    if (spriteSheet) return spriteSheet.textures[objectName];
    return '/assets/games/forestfires/hazard.json';
  }

  getObjectAspect(objectType: string, objectName: string): number {
    const spriteSheet = this.getSpriteSheetForType(objectType);
    return pixiUtil.getSpriteAspect(spriteSheet, objectName);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    /*if (this.taskId) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        null,
        null,
        this.updateIdeas,
        EndpointAuthorisationType.PARTICIPANT
      );
    }*/
  }

  updateIdeas(ideas: Idea[]): void {
    if (this.placedObjects.length === 0) {
      let placeables: { [key: number]: Placeable } = {};
      const levelsFromOthers = ideas
        .filter((idea) => idea.participantId !== authService.getParticipantId())
        .sort(() => Math.random() - 0.5);
      if (levelsFromOthers.length > 0) {
        placeables = levelsFromOthers[0].parameter as Placeable[];
      } else if (ideas.length > 0) {
        ideas = ideas.sort(() => Math.random() - 0.5);
        placeables = ideas[0].parameter as Placeable[];
      }
      for (const value of Object.values(placeables)) {
        if (!value.type) value.type = 'hazards';
        const configParameter = gameConfig[value.type][value.name];
        const placeable: Placeable = {
          uuid: uuidv4(),
          id: 0,
          type: value.type,
          name: value.name,
          texture: this.getTexture(value.type, value.name),
          width: configParameter.width,
          shape: configParameter.shape,
          position: value.position,
          rotation: value.rotation,
          scale: value.scale,
        };
        this.placedObjects.push(placeable);
      }
      this.totalCount = this.collectableObjects.length;
    }
  }

  get collectableObjects(): Placeable[] {
    return this.placedObjects.filter(
      (obj) => gameConfig[obj.type].settings.collectable
    );
  }

  get noneCollectableObjects(): Placeable[] {
    return this.placedObjects.filter(
      (obj) => !gameConfig[obj.type].settings.collectable
    );
  }

  @Watch('placedObjects.length', { immediate: false })
  onPlaceablesCountChanged(): void {
    if (this.collectableObjects.length === 0) {
      this.$emit('playFinished');
    }
  }

  destroyPlaceable(placeable: Placeable): void {
    const config = gameConfig[placeable.type].settings;
    if (config.collectable) {
      const id = placeable.id;
      const index = this.placedObjects.findIndex((p) => p.id === id);
      if (index > -1) this.placedObjects.splice(index, 1);
      this.collectedCount++;
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
