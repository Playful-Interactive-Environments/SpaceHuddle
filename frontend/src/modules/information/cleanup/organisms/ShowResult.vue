<template>
  <div class="gameArea">
    <GameContainer
      class="pixiCanvas"
      :detect-collision="false"
      :has-mouse-input="false"
      :use-gravity="false"
      :use-wind="true"
      v-model:width="gameWidth"
      v-model:height="gameHeight"
      @initRenderer="initRenderer"
    >
      <template v-slot:default>
        <container
          v-if="
            activeTabName &&
            particleVisualisation[activeTabName] &&
            ready &&
            gameWidth &&
            renderer
          "
        >
          <sprite
            :texture="`/assets/games/cleanup/${getParticleBackground(
              activeTabName
            )}`"
            :width="gameWidth"
            :height="gameHeight"
          ></sprite>
          <GameObject
            v-for="index of particleVisualisation[activeTabName].length"
            :key="`${activeTabName}.${index}`"
            type="circle"
            :x="getX(activeTabName, index - 1)"
            :y="getY(activeTabName, index - 1)"
          >
            <Graphics
              :radius="particleRadius"
              :color="getParticleColor(activeTabName, index)"
              @render="drawCircle"
            >
              <sprite
                :texture="getParticleTexture(activeTabName, index)"
                :anchor="0.5"
                :tint="getParticleColor(activeTabName, index)"
                :width="particleRadius * 1.5"
                :height="
                  getParticleAspect(activeTabName, index) * particleRadius * 1.5
                "
              >
              </sprite>
            </Graphics>
          </GameObject>
          <!--<Graphics v-if="renderer" :x="0" :y="0" @render="drawRect"></Graphics>-->
        </container>
      </template>
    </GameContainer>
    <el-carousel
      :height="`${gameHeight}px`"
      :interval="30000"
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
              `module.information.cleanup.participant.result.${successStatusText}`
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
          {{
            $t(`module.information.cleanup.enums.particle.${particleSource}`)
          }}
        </h1>
        <div>
          {{
            $t(
              `module.information.cleanup.participant.result.${particleSource}Explanation`
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
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ParticleState } from '@/modules/information/cleanup/organisms/CleanUpParticles.vue';
import * as gameConfig from '@/modules/information/cleanup/data/gameConfig.json';
import GameContainer from '@/components/shared/atoms/game/GameContainer.vue';
import GameObject from '@/components/shared/atoms/game/GameObject.vue';
import * as PIXI from 'pixi.js';
import * as pixiUtil from '@/utils/pixi';

@Options({
  components: { GameObject, GameContainer },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ShowResult extends Vue {
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
  particleRadius = 30;
  ready = false;
  activeTabName = 'result';
  spritesheet!: PIXI.Spritesheet;

  get particleStateSum(): ParticleState {
    let totalCount = 0;
    let collectedCount = 0;
    for (const particleName of Object.keys(this.particleState)) {
      totalCount += this.particleState[particleName].totalCount;
      collectedCount += this.particleState[particleName].collectedCount;
    }
    return { collectedCount: collectedCount, totalCount: totalCount };
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

  carouselChanged(index: number): void {
    if (index > 0) {
      this.activeTabName = Object.keys(this.particleState)[index - 1];
    } else {
      this.activeTabName = 'result';
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
      this.particleVisualisation[tabName].length > index
    ) {
      const particleName = this.particleVisualisation[tabName][index].type;
      return this.spritesheet.textures[particleName];
    }
    return null;
  }

  drawCircle(circle: PIXI.Graphics): void {
    pixiUtil.drawCircleWithGradient(circle, this.renderer);
  }

  drawRect(background: PIXI.Graphics): void {
    const width = this.gameWidth;
    const height = this.gameHeight;
    pixiUtil.drawRectWithGradient(
      background,
      this.renderer,
      width,
      height,
      '#ffffff',
      pixiUtil.GradientDirection.BottomTop
    );
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

  initRenderer(renderer: PIXI.Renderer): void {
    this.renderer = renderer;
  }

  mounted(): void {
    this.gameHeight = this.$el.parentElement.offsetHeight;
    PIXI.Assets.load('/assets/games/cleanup/molecules.json').then(
      (sheet) => (this.spritesheet = sheet)
    );
  }

  @Watch('gameWidth', { immediate: true })
  onSizeChanged(): void {
    if (this.gameWidth) {
      this.particleVisualisation['result'] = [];
      for (const particleName of Object.keys(this.particleState)) {
        const collectedCount = this.particleState[particleName].collectedCount;
        const remainingCount =
          this.particleState[particleName].totalCount - collectedCount;
        this.particleVisualisation[particleName] = [];
        for (let i = 0; i < remainingCount; i++) {
          this.particleVisualisation[particleName].push({
            coordinates: this.getRandomPosition(),
            type: particleName,
          });
        }
        for (let i = 0; i < collectedCount; i++) {
          this.particleVisualisation['result'].push({
            coordinates: this.getRandomPosition(),
            type: particleName,
          });
        }
      }
      this.ready = true;
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
    url('~@/modules/information/cleanup/assets/net.png');
}

.result {
  padding: 10rem;
  font-size: 5rem;
  font-width: var(--font-weight-bold);
  text-align: center;
}

.pixiCanvas {
  position: absolute;
  top: 0;
  right: 0;
}

.gameArea {
  height: 100%;
  width: 100%;
}
</style>
