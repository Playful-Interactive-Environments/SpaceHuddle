<template>
  <div class="gameArea">
    <GameContainer
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="false"
      :use-gravity="false"
      @initRenderer="initRenderer"
    >
      <template v-slot:default>
        <container v-if="gameWidth">
          <sprite
            texture="/assets/games/forestfires/background/forest.jpg"
            :width="3 * gameHeight"
            :height="gameHeight"
          ></sprite>
          <GameObject
            v-for="placeable in placedObjects"
            :key="placeable.uuid"
            v-model:id="placeable.id"
            :type="placeable.shape"
            :object-space="ObjectSpace.Relative"
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
            @click="destroyPlaceable(placeable)"
          >
            <CustomSprite
              :texture="placeable.texture"
              :anchor="0.5"
              :width="placeable.width"
              :aspect-ration="getObjectAspect(placeable.type, placeable.name)"
              :object-space="ObjectSpace.Relative"
            >
            </CustomSprite>
          </GameObject>
        </container>
      </template>
    </GameContainer>
    <div class="overlay-bottom">{{ collectedCount }} / {{ totalCount }}</div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Line } from 'vue-chartjs';
import { Application as Pixi } from 'vue3-pixi';
import * as PIXI from 'pixi.js';
import { Prop, Watch } from 'vue-property-decorator';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import Placeable from '@/modules/information/forestfires/types/Placeable';
import * as pixiUtil from '@/utils/pixi';
import * as ideaService from '@/services/idea-service';
import * as cashService from '@/services/cash-service';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as authService from '@/services/auth-service';
import { ObjectSpace } from '@/types/enum/ObjectSpace';
import CustomSprite from '@/components/shared/atoms/game/CustomSprite.vue';
import { v4 as uuidv4 } from 'uuid';
import gameConfig from '@/modules/information/forestfires/data/gameConfig.json';

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpace;
    },
  },
  components: {
    GameObject,
    GameContainer,
    CustomSprite,
    Line,
    Pixi,
  },
  emits: ['playFinished'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PlayState extends Vue {
  @Prop() readonly taskId!: string;
  renderer!: PIXI.Renderer;
  gameWidth = 0;
  gameHeight = 0;

  placedObjects: Placeable[] = [];
  hazardSpritesheet!: PIXI.Spritesheet;
  obstacleSpritesheet!: PIXI.Spritesheet;
  totalCount = 0;
  collectedCount = 0;

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

  getTexture(objectType: string, objectName: string): PIXI.Texture | string {
    if (objectType === 'hazards' && this.hazardSpritesheet)
      return this.hazardSpritesheet.textures[objectName];
    if (objectType === 'obstacles' && this.obstacleSpritesheet)
      return this.obstacleSpritesheet.textures[objectName];
    return '/assets/games/forestfires/hazard.json';
  }

  getObjectAspect(objectType: string, objectName: string): number {
    if (objectType === 'hazards')
      return pixiUtil.getSpriteAspect(this.hazardSpritesheet, objectName);
    return pixiUtil.getSpriteAspect(this.obstacleSpritesheet, objectName);
  }

  getHazardAspect(hazardName: string): number {
    return pixiUtil.getSpriteAspect(this.hazardSpritesheet, hazardName);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    if (this.taskId) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        null,
        null,
        this.updateIdeas,
        EndpointAuthorisationType.PARTICIPANT
      );
    }
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
      this.totalCount = this.placedObjects.length;
    }
  }

  @Watch('placedHazards.length', { immediate: false })
  onPlaceablesCountChanged(): void {
    if (this.placedObjects.length === 0) {
      this.$emit('playFinished');
    }
  }

  destroyPlaceable(placeable: Placeable): void {
    const id = placeable.id;
    const index = this.placedObjects.findIndex((p) => p.id === id);
    if (index > -1) this.placedObjects.splice(index, 1);
    this.collectedCount++;
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
