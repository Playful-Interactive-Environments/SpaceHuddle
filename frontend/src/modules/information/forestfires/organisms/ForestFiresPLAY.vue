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
            @click="destroyPlaceable(hazard)"
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
import { Line } from 'vue-chartjs';
import { Application as Pixi } from 'vue3-pixi';
import * as PIXI from 'pixi.js';
import { Watch } from 'vue-property-decorator';

// Custom Modules
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import Placeable, {
  PlaceableList,
} from '@/modules/information/forestfires/types/Placeable';

@Options({
  components: {
    GameObject,
    GameContainer,
    Line,
    Pixi,
  },
  emits: ['playFinished'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ForestFirePlay extends Vue {
  renderer!: PIXI.Renderer;
  gameWidth = 0;
  gameHeight = 0;

  placedHazards: Placeable[] = PlaceableList;

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
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
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
