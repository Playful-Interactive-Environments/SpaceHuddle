<template>
  <div class="infoArea">
    <SpriteCanvas
      v-if="
        mappedModuleInfoEntryDataList.length > 0 &&
        mappedModuleInfoEntryDataList[activeTabIndex] &&
        !isDefaultImage(mappedModuleInfoEntryDataList[activeTabIndex].texture)
      "
      class="pixiCanvas info-image"
      :width="gameWidth"
      :height="gameHeight / 2"
      :texture="mappedModuleInfoEntryDataList[activeTabIndex].texture"
    />
    <el-carousel
      ref="carousel"
      v-if="gameHeight"
      :autoplay="false"
      arrow="always"
      :height="`${gameHeight}px`"
      trigger="click"
      :loop="false"
      @change="carouselChanged"
    >
      <el-carousel-item
        v-for="entry of openModuleInfoEntryDataList"
        :key="entry.key"
      >
        <img
          v-if="isDefaultImage(entry.texture)"
          class="info-image"
          :src="entry.texture"
          :alt="entry.key"
        />
        <div class="info-text">
          {{ $t(`${translationPath}.${entry.key}`) }}
        </div>
      </el-carousel-item>
    </el-carousel>
    <div class="next">
      <el-button type="primary" @click="next">
        {{ $t('participant.molecules.moduleInfo.next') }}
      </el-button>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Tutorial } from '@/types/api/Tutorial';
import * as tutorialService from '@/services/tutorial-service';
import * as cashService from '@/services/cash-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import SpriteCanvas from '@/components/shared/atoms/game/SpriteCanvas.vue';
import * as PIXI from 'pixi.js';

export interface ModuleInfoEntryData {
  key: string;
  texture: string | PIXI.Texture | PIXI.Texture[] | string[];
}

@Options({
  components: { SpriteCanvas },
  emits: ['infoRead'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleInfo extends Vue {
  @Prop({ default: [] })
  readonly moduleInfoEntryDataList!: (string | ModuleInfoEntryData)[];
  @Prop({ default: '' }) readonly translationPath!: string;
  @Prop({ default: '' }) readonly imageDirectory!: string;
  @Prop({ default: 'jpg' }) readonly fileExtension!: string;
  @Prop({ default: 'moduleInfo' }) readonly infoType!: string;
  @Prop({ default: true }) readonly showTutorialOnlyOnce!: boolean;
  gameWidth = 0;
  gameHeight = 0;
  tutorialSteps: Tutorial[] = [];
  activeTabIndex = 0;

  get openModuleInfoEntryDataList(): ModuleInfoEntryData[] {
    return this.mappedModuleInfoEntryDataList.filter(
      (entry) => !this.getIncludeStep(entry.key)
    );
  }

  get mappedModuleInfoEntryDataList(): ModuleInfoEntryData[] {
    return this.moduleInfoEntryDataList.map((entry) => {
      if (typeof entry === 'string')
        return {
          key: entry,
          texture: `${this.imageDirectory}/${entry}.${this.fileExtension}`,
        };
      else if (
        typeof entry.texture === 'string' &&
        !entry.texture.includes('\\')
      ) {
        return {
          key: entry.key,
          texture: `${this.imageDirectory}/${entry.texture}`,
        };
      }
      return entry;
    });
  }

  get indexDelta(): number {
    return (
      this.moduleInfoEntryDataList.length -
      this.openModuleInfoEntryDataList.length
    );
  }

  mounted(): void {
    this.gameWidth = this.$el.parentElement.offsetWidth;
    this.gameHeight = this.$el.parentElement.offsetHeight;
    tutorialService.registerGetList(
      this.updateTutorial,
      EndpointAuthorisationType.PARTICIPANT
    );
  }

  isDefaultImage(
    texture: string | PIXI.Texture | PIXI.Texture[] | string[]
  ): boolean {
    if (typeof texture === 'string') return !texture.endsWith('.json');
    return false;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTutorial);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  initTutorialDone = false;
  updateTutorial(steps: Tutorial[]): void {
    this.tutorialSteps = steps;
    if (!this.initTutorialDone) this.activeTabIndex = this.indexDelta;
    if (this.openModuleInfoEntryDataList.length === 0) {
      this.$emit('infoRead');
    }
    this.initTutorialDone = true;
  }

  getIncludeStep(stepName: string): boolean {
    if (this.showTutorialOnlyOnce) {
      return !!this.tutorialSteps.find(
        (tutorial) =>
          tutorial.step == stepName && tutorial.type == this.infoType
      );
    }
    return false;
  }

  minCarouselClickDelay = 500;
  carouselEvent = {
    lastChanged: 0,
    lastValue: 0,
  };
  carouselChanged(index: number): void {
    if (
      this.carouselEvent.lastChanged + this.minCarouselClickDelay <
      Date.now()
    ) {
      if (this.activeTabIndex < this.indexDelta + index)
        this.storeInfoStepRead();
      this.activeTabIndex = this.indexDelta + index;
      this.carouselEvent = {
        lastChanged: Date.now(),
        lastValue: index,
      };
    } else {
      (this.$refs.carousel as any).setActiveItem(this.carouselEvent.lastValue);
    }
  }

  next(): void {
    if (this.activeTabIndex + 1 === this.moduleInfoEntryDataList.length) {
      this.storeInfoStepRead();
      this.$emit('infoRead');
    } else {
      (this.$refs.carousel as any).next();
    }
  }

  storeInfoStepRead(): void {
    const stepName = this.mappedModuleInfoEntryDataList[this.activeTabIndex];
    if (!this.getIncludeStep(stepName.key)) {
      const tutorialItem: Tutorial = {
        step: stepName.key,
        type: this.infoType,
        order: this.activeTabIndex,
      };
      tutorialService.addTutorialStep(
        tutorialItem,
        EndpointAuthorisationType.PARTICIPANT,
        this.eventBus
      );
    }
  }
}
</script>

<style scoped lang="scss">
.infoArea {
  position: relative;
  height: 100%;
  width: 100%;
}

.pixiCanvas {
  position: absolute;
  top: 0;
  right: 0;
}

.el-carousel__item div {
  padding: 2rem;
}

img {
  max-height: 50%;
  height: 50%;
  width: 100%;
  object-fit: contain;
}

.next {
  padding: 2rem;
  position: fixed;
  bottom: 0;
}

.info-image {
  max-height: 50vh;
  height: 50vh;
}

.info-text {
  position: absolute;
  top: calc(50vh + 1rem);
}
</style>
