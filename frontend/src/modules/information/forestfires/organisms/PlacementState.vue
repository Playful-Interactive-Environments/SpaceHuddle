<template>
  <div class="gameArea">
    <GameContainer
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      :detect-collision="false"
      :use-gravity="false"
      :use-borders="false"
      @click="placeHazard"
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
            v-for="hazard in placedHazards"
            :key="hazard.uuid"
            v-model:id="hazard.id"
            :type="hazard.shape"
            :object-space="ObjectSpace.Relative"
            v-model:x="hazard.position[0]"
            v-model:y="hazard.position[1]"
            v-model:rotation="hazard.rotation"
            :options="{
              name: hazard.name,
              collisionFilter: {
                group: 'hazard',
                category: 0x0001,
                mask: 0x0001,
              },
            }"
            :source="hazard"
            :use-physic="false"
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
    <div
      class="overlay-top"
      v-if="availableHazards.length === 0"
      @click="showLevelSettings = true"
    >
      <div>
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
    </div>
  </div>
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
  gameWidth = 0;
  gameHeight = 0;

  availableHazards: Placeable[] = [];
  placedHazards: Placeable[] = [];
  placementState: { [key: string]: PlacementState } = {};
  hazardSpritesheet!: PIXI.Spritesheet;
  showLevelSettings = false;
  selectedObject: GameObject | null = null;

  mounted(): void {
    PIXI.Assets.load('/assets/games/forestfires/hazard.json').then(
      (sheet) => (this.hazardSpritesheet = sheet)
    );

    // Set up hazard state counters
    for (const hazardName in gameConfig.hazards) {
      const hazardParameter = gameConfig.hazards[hazardName];
      this.placementState[hazardName] = {
        maxCount: hazardParameter.maxCount,
        currentCount: 0,
      };

      for (let i = 0; i < hazardParameter.maxCount; i++) {
        const placeable: Placeable = {
          uuid: uuidv4(),
          id: 0,
          name: hazardName,
          width: hazardParameter.width,
          shape: hazardParameter.shape,
          position: [0, 0],
          rotation: 0,
        };
        this.availableHazards.push(placeable);
      }
    }

    this.availableHazards = this.availableHazards.sort(
      () => Math.random() - 0.5
    );
  }

  getHazardAspect(hazardName: string): number {
    return pixiUtil.getSpriteAspect(this.hazardSpritesheet, hazardName);
  }

  @Watch('availableHazards.length', { immediate: false })
  onPlaceablesCountChanged(): void {
    if (this.availableHazards.length === 0) {
      this.showLevelSettings = true;
    }
  }

  saveLevel(name: string) {
    ideaService.postIdea(
      this.taskId,
      {
        keywords: name,
        parameter: this.placedHazards,
      },
      EndpointAuthorisationType.PARTICIPANT
    );
    this.$emit('editFinished');
  }

  placeHazard(event: MouseConstraint): void {
    setTimeout(() => {
      const currentHazard = this.availableHazards.pop();
      if (currentHazard) {
        currentHazard.position = [
          (event.mouse.position.x / this.gameWidth) * 100,
          (event.mouse.position.y / this.gameHeight) * 100,
        ];
        this.placedHazards.push(currentHazard);
      } else {
        ElMessage({
          message: this.$t(
            'module.information.forestfires.participant.everythingPlaced'
          ),
          type: 'error',
          center: true,
          showClose: true,
        });
      }
    }, 100);
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
  font-size: var(--font-size-xxxxlarge);
  color: white;
}

.overlay-selected-item {
  z-index: 100;
  position: absolute;
  //top: var(--x);
  //left: var(--y);
  top: 1rem;
  left: 1rem;
}
</style>
