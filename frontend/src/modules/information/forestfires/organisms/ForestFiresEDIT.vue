<template>
  <div class="gameArea">
    <GameContainer
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :collisionsFilter="(collision: Matter.Collision) => {
            return collision.bodyA.isStatic !== collision.bodyB.isStatic;
          }"
      :detect-collision="false"
      :use-gravity="false"
      @initRenderer="initRenderer"
      @click="placeHazard"
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
            v-model:x="hazard.position[0]"
            v-model:y="hazard.position[1]"
            :options="{
              name: hazard.name,
              collisionFilter: {
                group: hazard.group,
                category: 0x0001,
                mask: 0x0001,
              },
            }"
            :is-static="true"
          >
            <sprite
              :texture="
                '/assets/games/forestfires/placeables/' + hazard.name + '.svg'
              "
              :anchor="0.5"
              :width="hazard.size"
              :height="hazard.size"
            >
            </sprite>
          </GameObject>
        </container>
      </template>
    </GameContainer>
  </div>
</template>

<script lang="ts">
// Vue
import { Options, Vue } from 'vue-class-component';
import { Watch } from 'vue-property-decorator';
import { Line } from 'vue-chartjs';
import { Application as Pixi } from 'vue3-pixi';
import * as PIXI from 'pixi.js';

// Custom Modules
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import gameConfig from '@/modules/information/forestfires/data/gameConfig.json';
import Placeable, {
  PlaceableList,
} from '@/modules/information/forestfires/types/Placeable';
import { v4 as uuidv4 } from 'uuid';

// Matter
import { MouseConstraint } from 'matter-js';

// The current state of the edit mode
export interface PlacementState {
  maxCount: number;
  currentCount: number;
}

@Options({
  components: {
    GameObject,
    GameContainer,
    Line,
    Pixi,
  },
  emits: ['editFinished'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ForestFireEdit extends Vue {
  renderer!: PIXI.Renderer;
  gameWidth = 0;
  gameHeight = 0;

  availableHazards: Placeable[] = [];
  placedHazards: Placeable[] = [];
  placementState: { [key: string]: PlacementState } = {};

  mounted(): void {
    // Set up hazard state counters
    for (const hazardName in gameConfig.hazards) {
      switch (hazardName) {
        case 'cigarette':
          this.placementState[hazardName] = {
            maxCount: 2,
            currentCount: 0,
          };

          for (let i = 0; i < this.placementState[hazardName].maxCount; i++) {
            const placeable: Placeable = {
              uuid: uuidv4(),
              id: 0,
              name: hazardName,
              size: 16,
              group: i + 1,
              position: [0, 0],
            };
            this.availableHazards.push(placeable);
          }

          break;
        case 'bottleWhite':
          this.placementState[hazardName] = {
            maxCount: 1,
            currentCount: 0,
          };

          for (let i = 0; i < this.placementState[hazardName].maxCount; i++) {
            const placeable: Placeable = {
              uuid: uuidv4(),
              id: 0,
              name: hazardName,
              size: 32,
              group: i + 1,
              position: [0, 0],
            };
            this.availableHazards.push(placeable);
          }

          break;
        case 'bottleWine':
          this.placementState[hazardName] = {
            maxCount: 1,
            currentCount: 0,
          };

          for (let i = 0; i < this.placementState[hazardName].maxCount; i++) {
            const placeable: Placeable = {
              uuid: uuidv4(),
              id: 0,
              name: hazardName,
              size: 32,
              group: i + 1,
              position: [0, 0],
            };
            this.availableHazards.push(placeable);
          }

          break;
        case 'bottleBeer':
          this.placementState[hazardName] = {
            maxCount: 1,
            currentCount: 0,
          };

          for (let i = 0; i < this.placementState[hazardName].maxCount; i++) {
            const placeable: Placeable = {
              uuid: uuidv4(),
              id: 0,
              name: hazardName,
              size: 32,
              group: i + 1,
              position: [0, 0],
            };
            this.availableHazards.push(placeable);
          }

          break;
        default:
          this.placementState[hazardName] = {
            maxCount: 3,
            currentCount: 0,
          };

          for (let i = 0; i < this.placementState[hazardName].maxCount; i++) {
            const placeable: Placeable = {
              uuid: uuidv4(),
              id: 0,
              name: hazardName,
              size: 32,
              group: i + 1,
              position: [0, 0],
            };
            this.availableHazards.push(placeable);
          }

          break;
      }
    }

    this.availableHazards = this.shuffle(this.availableHazards);
  }

  @Watch('availableHazards.length', { immediate: false })
  onPlaceablesCountChanged(): void {
    if (this.availableHazards.length === 0) {
      this.$emit('editFinished');
    }
  }

  placeHazard(event: MouseConstraint): void {
    const currentHazard = this.availableHazards.pop();
    if (currentHazard) {
      currentHazard.position = [event.mouse.position.x, event.mouse.position.y];
      PlaceableList.push(currentHazard);
      this.placedHazards.push(currentHazard);
    }
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
  }

  shuffle(array: any[]): any[] {
    let currentIndex = array.length,
      randomIndex;

    while (currentIndex != 0) {
      randomIndex = Math.floor(Math.random() * currentIndex);
      currentIndex--;

      [array[currentIndex], array[randomIndex]] = [
        array[randomIndex],
        array[currentIndex],
      ];
    }

    return array;
  }
}
</script>

<style scoped lang="scss">
.chartArea {
  position: relative;
  height: 10rem;
  width: 100%;
}

.overlay {
  position: absolute;
  top: 0;
  height: 10rem;
  width: 100%;
  background-color: #ffffff99;
  font-size: var(--font-size-xxxxlarge);
  text-align: center;
  vertical-align: center;
}

.gameArea {
  height: calc(100% - 10rem);
  width: 100%;
}

.custom-renderer-wrapper {
  height: 100%;
}
</style>
