<template>
  <div class="gameArea">
    <GameContainer
      class="pixiCanvas"
      :detect-collision="false"
      :has-mouse-input="false"
      :use-gravity="false"
      :use-wind="true"
      :background-texture="`/assets/games/moveit/${getParticleBackground(
        activeTabName
      )}`"
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      @initRenderer="initRenderer"
    >
      <template v-slot:default>
        <container
          v-if="
            activeTabName &&
            particleVisualisation[activeTabName] &&
            particleReady &&
            rendererReady &&
            gameWidth
          "
        >
          <GameObject
            v-for="index of particleVisualisation[activeTabName].length"
            :key="`${activeTabName}.${index}`"
            type="circle"
            :x="getX(activeTabName, index - 1)"
            :y="getY(activeTabName, index - 1)"
            :fix-size="particleRadius * 2"
          >
            <sprite
              v-if="getParticleTexture(activeTabName, index - 1)"
              :texture="getParticleTexture(activeTabName, index - 1)"
              :width="particleRadius * 2"
              :height="particleRadius * 2"
              :anchor="0.5"
              :tint="getParticleColor(activeTabName, index - 1)"
            />
          </GameObject>
        </container>
      </template>
    </GameContainer>
    <div class="infoOverlay">
      <el-carousel
        class="resultInfo"
        :height="`${gameHeight}px`"
        :interval="30000"
        trigger="click"
        @change="carouselChanged"
      >
        <el-carousel-item class="infoGraphic successState">
          <h1>
            <el-rate
              v-model="successRate"
              size="large"
              :max="3"
              :disabled="true"
            />
          </h1>
          <div>
            {{
              $t(
                `module.playing.moveit.participant.result.${successStatusText}`
              )
            }}
          </div>
          <div class="result">
            {{ particleStateSum.collectedCount }}
            /
            {{ particleStateSum.totalCount }}
          </div>
        </el-carousel-item>
        <el-carousel-item
          class="infoGraphic"
          v-for="particleSource of Object.keys(gameConfig.particles)"
          :key="particleSource"
        >
          <h1>
            {{ $t(`module.playing.moveit.enums.particle.${particleSource}`) }}
          </h1>
          <div>
            {{
              $t(
                `module.playing.moveit.participant.result.${particleSource}Explanation`
              )
            }}
          </div>
          <div class="result">
            {{
              particleState[particleSource].totalCount -
              particleState[particleSource].collectedCount
            }}
            /
            {{ particleState[particleSource].totalCount }}
          </div>
        </el-carousel-item>
      </el-carousel>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ParticleState } from '@/modules/playing/moveit/organisms/CleanUpParticles.vue';
import * as gameConfig from '@/modules/playing/moveit/data/gameConfig.json';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import * as PIXI from 'pixi.js';
import * as pixiUtil from '@/utils/pixi';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import * as vehicleCalculation from '@/modules/playing/moveit/types/Vehicle';

const resultTabName = 'result';

@Options({
  components: { GameObject, GameContainer },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ShowResult extends Vue {
  @Prop() readonly trackingManager!: TrackingManager;
  @Prop({
    default: {
      carbonDioxide: { collectedCount: 0, totalCount: 0 },
      dust: { collectedCount: 0, totalCount: 0 },
      methane: { collectedCount: 0, totalCount: 0 },
      microplastic: { collectedCount: 0, totalCount: 0 },
    },
  })
  readonly particleState!: {
    [key: string]: ParticleState;
  };
  particleVisualisation: {
    [key: string]: { coordinates: [number, number]; type: string }[];
  } = {};
  gameHeight = 0;
  gameWidth = 0;
  gameConfig = gameConfig;
  renderer!: PIXI.Renderer;
  particleReady = false;
  rendererReady = false;
  activeTabName = resultTabName;
  spritesheet!: PIXI.Spritesheet;
  maxParticleCount = 100;
  circleGradientTexture: PIXI.Texture | null = null;
  particleTextures: { [key: string]: PIXI.Texture } = {};

  get particleStateSum(): ParticleState {
    let totalCount = 0;
    let collectedCount = 0;
    for (const particleName of Object.keys(this.particleState)) {
      totalCount += this.particleState[particleName].totalCount;
      collectedCount += this.particleState[particleName].collectedCount;
    }
    return {
      collectedCount: collectedCount,
      totalCount: totalCount,
    };
  }

  get successStatus(): number {
    const sum = this.particleStateSum;
    return (sum.collectedCount / sum.totalCount) * 100;
  }

  get successStatusText(): string {
    const successStatus = this.successStatus;
    if (successStatus > 90) {
      return 'veryGood';
    }
    if (successStatus > 70) {
      return 'good';
    }
    if (successStatus > 50) {
      return 'notBad';
    }
    return 'improvable';
  }

  get successRate(): number {
    const successStatus = this.successStatus;
    if (successStatus > 90) {
      return 3;
    }
    if (successStatus > 70) {
      return 2;
    }
    if (successStatus > 50) {
      return 1;
    }
    return 0;
  }

  get particleArea(): number {
    return this.gameWidth * this.gameHeight;
  }

  get particleRadius(): number {
    const minSize = 10;
    const maxSize = 40;
    const circleArea = this.particleArea / (this.maxParticleCount * 2);
    const size = Math.sqrt(circleArea / Math.PI);
    if (size > maxSize) return maxSize;
    if (size < minSize) return minSize;
    return size;
  }

  carouselChanged(index: number): void {
    if (index > 0) {
      this.activeTabName = Object.keys(this.particleState)[index - 1];
    } else {
      this.activeTabName = resultTabName;
    }
  }

  getParticleBackground(name: string): string {
    switch (name) {
      case 'carbonDioxide':
      case 'methane':
        return 'sky.jpg';
      case 'dust':
        return 'body.png';
      case 'microplastic':
        return 'water.jpg';
    }
    return 'sky.jpg';
  }

  getParticleAspect(tabName: string, index: number): number {
    if (
      this.particleVisualisation[tabName] &&
      this.particleVisualisation[tabName].length > index
    ) {
      const particleName = this.particleVisualisation[tabName][index].type;
      if (
        this.spritesheet?.data?.frames &&
        particleName in this.spritesheet.data.frames
      ) {
        const h = this.spritesheet.data.frames[particleName].sourceSize?.h;
        const w = this.spritesheet.data.frames[particleName].sourceSize?.w;
        if (h && w) return h / w;
      }
    }
    return 1;
  }

  getParticleColor(tabName: string, index: number): string {
    if (
      this.particleVisualisation[tabName] &&
      this.particleVisualisation[tabName].length > index
    ) {
      const particleName = this.particleVisualisation[tabName][index].type;
      return gameConfig.particles[particleName].color;
    }
    return '#ffffff';
  }

  getParticleTexture(tabName: string, index: number): PIXI.Texture | null {
    if (
      this.particleVisualisation[tabName] &&
      this.particleVisualisation[tabName].length > index &&
      this.spritesheet
    ) {
      const particleName = this.particleVisualisation[tabName][index].type;
      if (this.particleTextures[particleName])
        return this.particleTextures[particleName];
      return this.spritesheet.textures[particleName];
    }
    return null;
  }

  getRandomPosition(): [number, number] {
    const x = Math.round(
      Math.random() * (this.gameWidth - this.particleRadius * 2) +
        this.particleRadius
    );
    const borderTop = this.gameHeight / 4;
    const y = Math.round(
      Math.random() * (this.gameHeight - borderTop - this.particleRadius * 2) +
        this.particleRadius +
        borderTop
    );
    return [x, y];
  }

  getX(tabName: string, index: number): number {
    if (
      this.particleVisualisation[tabName] &&
      this.particleVisualisation[tabName].length > index
    )
      return this.particleVisualisation[tabName][index].coordinates[0];
    return 0;
  }

  getY(tabName: string, index: number): number {
    if (
      this.particleVisualisation[tabName] &&
      this.particleVisualisation[tabName].length > index
    )
      return this.particleVisualisation[tabName][index].coordinates[1];
    return 0;
  }

  async generateParticleTextures(): Promise<void> {
    if (
      !this.renderer ||
      !this.circleGradientTexture ||
      !this.spritesheet ||
      Object.keys(this.particleTextures).length > 0
    )
      return;
    for (const particleName of Object.keys(gameConfig.particles)) {
      if (this.spritesheet.textures[particleName]) {
        this.particleTextures[particleName] = pixiUtil.generateStackedTexture(
          [this.circleGradientTexture, this.spritesheet.textures[particleName]],
          this.renderer,
          60
        );
      }
    }
  }

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
    this.circleGradientTexture = pixiUtil.generateCircleGradientTexture(
      this.particleRadius,
      this.renderer
    );
    this.rendererReady = true;
    this.generateParticleTextures();
  }

  async mounted(): Promise<void> {
    this.gameHeight = this.$el.parentElement.offsetHeight;
    if (this.gameWidth) {
      this.particleVisualisation[resultTabName] = [];
      for (const particleName of Object.keys(this.particleState)) {
        this.particleVisualisation[particleName] = [];
      }
    }
    pixiUtil
      .loadTexture('/assets/games/moveit/molecules.json', this.eventBus)
      .then((sheet) => {
        this.spritesheet = sheet;
        this.generateParticleTextures();
      });

    if (
      this.trackingManager &&
      this.trackingManager.state &&
      this.trackingManager.state.parameter.rate < this.successRate
    ) {
      this.trackingManager.saveState({ rate: this.successRate });
    }

    if (this.trackingManager && this.trackingManager.iteration) {
      await this.trackingManager.saveIteration(
        {
          rate: this.successRate,
        },
        this.successRate >= 2
          ? TaskParticipantIterationStatesType.WIN
          : TaskParticipantIterationStatesType.LOOS
      );
    }

    if (this.trackingManager && this.trackingManager.iterationStep) {
      const vehicle = this.trackingManager.iterationStep.parameter.vehicle;
      await this.trackingManager.saveIterationStep(
        {
          rate: this.successRate,
        },
        this.successRate === 3
          ? TaskParticipantIterationStepStatesType.CORRECT
          : TaskParticipantIterationStepStatesType.WRONG,
        this.successRate,
        null,
        true,
        (item) =>
          vehicleCalculation.isSameVehicle(item.parameter.vehicle, vehicle)
      );
    }
  }

  unmounted(): void {
    pixiUtil.unloadTexture('/assets/games/moveit/molecules.json');
  }

  @Watch('gameWidth', { immediate: true })
  onSizeChanged(): void {
    if (this.gameWidth) {
      this.particleVisualisation[resultTabName] = [];
      for (const particleName of Object.keys(this.particleState)) {
        const collectedCount = this.particleState[particleName].collectedCount;
        const remainingCount =
          this.particleState[particleName].totalCount - collectedCount;
        this.particleVisualisation[particleName] = [];
        for (let i = 0; i < remainingCount; i++) {
          if (
            this.particleVisualisation[particleName].length <
            this.maxParticleCount
          ) {
            this.particleVisualisation[particleName].push({
              coordinates: this.getRandomPosition(),
              type: particleName,
            });
          }
        }
        for (let i = 0; i < collectedCount; i++) {
          if (
            this.particleVisualisation[resultTabName].length <
            this.maxParticleCount
          ) {
            this.particleVisualisation[resultTabName].push({
              coordinates: this.getRandomPosition(),
              type: particleName,
            });
          }
        }
      }
      this.particleReady = true;
    }
  }
}
</script>

<style scoped lang="scss">
h1 {
  padding: 1rem;
  font-size: var(--font-size-xxlarge);
  font-width: var(--font-weight-bold);
  text-align: center;
}

.infoGraphic {
  padding: 1rem;
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center;
  background-image: linear-gradient(#ffffffee, #ffffff00);
}

.successState {
  background-image: linear-gradient(#ffffffee, #ffffff00),
    url('~@/modules/playing/moveit/assets/net.png');
}

.result {
  padding-top: 5rem;
  font-size: 3rem;
  font-width: var(--font-weight-bold);
  text-align: center;
}

.pixiCanvas {
  position: absolute;
  top: 0;
  right: 0;
}

.infoOverlay {
  position: absolute;
  top: 0;
  bottom: 0;
  right: 0;
  left: 0;
}

.gameArea {
  height: 100%;
  width: 100%;
}

.el-rate::v-deep(.el-icon) {
  height: 5em;
  width: 5em;

  svg {
    height: 5em;
    width: 5em;
  }
}
</style>
