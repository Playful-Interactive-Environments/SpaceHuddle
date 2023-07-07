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
            v-for="hazard in placedHazards"
            :key="hazard.uuid"
            v-model:id="hazard.id"
            type="rect"
            :object-space="ObjectSpace.Relative"
            :x="hazard.position[0]"
            :y="hazard.position[1]"
            :options="{
              name: hazard.name,
              collisionFilter: {
                group: hazard.group,
                category: 0x0001,
                mask: 0x0001,
              },
            }"
            :is-static="true"
            @pointerdown="destroyPlaceable(hazard)"
          >
            <CustomSprite
              :texture="hazardSpritesheet.textures[hazard.name]"
              :anchor="0.5"
              :width="hazard.width"
              :aspect-ration="getHazardAspect(hazard.name)"
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

@Options({
  computed: {
    ObjectSpace() {
      return ObjectSpace;
    },
  },
  components: {
    GameObject,
    GameContainer,
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

  placedHazards: Placeable[] = [];
  hazardSpritesheet!: PIXI.Spritesheet;
  totalCount = 0;
  collectedCount = 0;

  mounted(): void {
    PIXI.Assets.load('/assets/games/forestfires/hazard.json').then(
      (sheet) => (this.hazardSpritesheet = sheet)
    );
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateIdeas);
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
    if (this.placedHazards.length === 0) {
      let hazards: { [key: number]: Placeable } = {};
      const levelsFromOthers = ideas
        .filter((idea) => idea.participantId !== authService.getParticipantId())
        .sort(() => Math.random() - 0.5);
      if (levelsFromOthers.length > 0) {
        hazards = levelsFromOthers[0].parameter as Placeable[];
      } else if (ideas.length > 0) {
        ideas = ideas.sort(() => Math.random() - 0.5);
        hazards = ideas[0].parameter as Placeable[];
      }
      for (const value of Object.values(hazards))
        this.placedHazards.push(value);
      this.totalCount = this.placedHazards.length;
    }
  }

  @Watch('placedHazards.length', { immediate: false })
  onPlaceablesCountChanged(): void {
    if (this.placedHazards.length === 0) {
      this.$emit('playFinished');
    }
  }

  destroyPlaceable(placeable: Placeable): void {
    const id = placeable.id;
    const index = this.placedHazards.findIndex((p) => p.id === id);
    if (index > -1) this.placedHazards.splice(index, 1);
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
