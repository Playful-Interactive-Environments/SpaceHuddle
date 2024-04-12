<template>
  <div class="infoArea">
    <slot name="prefix" />
    <el-carousel
      ref="carousel"
      v-if="gameHeight"
      :autoplay="false"
      arrow="never"
      :height="`${gameHeight}px`"
      trigger="click"
      :loop="false"
      @change="carouselChanged"
    >
      <el-carousel-item
        v-for="entry of openModuleInfoEntryDataList"
        :key="entry"
        class="info-text"
      >
        <slot :key="entry" />
      </el-carousel-item>
      <slot name="content" />
    </el-carousel>
    <div class="next">
      <el-button type="primary" @click="next">
        <p>{{ $t('participant.molecules.moduleInfo.next') }}</p>
      </el-button>
    </div>
    <div class="prev">
      <el-button type="primary" @click="prev" v-if="activeTabIndex - 1 >= 0">
        <p>{{ $t('participant.molecules.moduleInfo.prev') }}</p>
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
import { registerDomElement, unregisterDomElement } from '@/vunit';

@Options({
  components: { SpriteCanvas },
  emits: ['infoRead', 'sizeChanged', 'activeTabIndexChanged'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantTutorial extends Vue {
  @Prop({ default: [] })
  readonly moduleInfoEntryDataList!: string[];
  @Prop({ default: 'moduleInfo' }) readonly infoType!: string;
  @Prop({ default: true }) readonly showTutorialOnlyOnce!: boolean;
  tutorialSteps: Tutorial[] = [];
  activeTabIndex = 0;
  gameHeight = 0;

  get openModuleInfoEntryDataList(): string[] {
    return this.moduleInfoEntryDataList.filter(
      (entry) => !this.getIncludeStep(entry)
    );
  }

  get indexDelta(): number {
    return (
      this.moduleInfoEntryDataList.length -
      this.openModuleInfoEntryDataList.length
    );
  }

  domKey = '';
  mounted(): void {
    tutorialService.registerGetList(
      this.updateTutorial,
      EndpointAuthorisationType.PARTICIPANT
    );
    this.domKey = registerDomElement(
      this.$el,
      (targetWidth, targetHeight) => {
        this.$emit('sizeChanged', targetWidth, targetHeight);
        this.gameHeight = targetHeight;
      },
      0,
      false,
      () => {
        this.$emit('sizeChanged', 0, 0);
        this.gameHeight = 0;
      }
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTutorial);
  }

  unmounted(): void {
    this.deregisterAll();
    unregisterDomElement(this.domKey);
  }

  initTutorialDone = false;
  updateTutorial(steps: Tutorial[]): void {
    this.tutorialSteps = steps;
    if (!this.initTutorialDone) {
      this.activeTabIndex = this.indexDelta;
      this.$emit('activeTabIndexChanged', this.activeTabIndex);
    }
    if (this.openModuleInfoEntryDataList.length === 0) {
      this.$emit('infoRead');
    }
    this.initTutorialDone = true;
  }

  getIncludeStep(stepName: string): boolean {
    console.log(this.showTutorialOnlyOnce, this.tutorialSteps);
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
      this.$emit('activeTabIndexChanged', this.activeTabIndex);
      this.carouselEvent = {
        lastChanged: Date.now(),
        lastValue: index,
      };
    } else {
      (this.$refs.carousel as any).setActiveItem(this.carouselEvent.lastValue);
    }
  }

  next(): void {
    if (this.activeTabIndex + 1 >= this.openModuleInfoEntryDataList.length) {
      this.storeInfoStepRead();
      this.$emit('infoRead');
    } else {
      (this.$refs.carousel as any).next();
    }
  }

  prev(): void {
    if (this.activeTabIndex - 1 >= 0) {
      (this.$refs.carousel as any).prev();
    }
  }

  storeInfoStepRead(): void {
    const stepName = this.moduleInfoEntryDataList[this.activeTabIndex];
    if (!this.getIncludeStep(stepName)) {
      const tutorialItem: Tutorial = {
        step: stepName,
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

img {
  max-height: 50%;
  height: 50%;
  width: 100%;
  object-fit: contain;
}

.next {
  padding: 0 2rem;
  position: absolute;
  right: 0;
  height: calc(6vh);
  bottom: 1rem;
}

.prev {
  padding: 0 2rem;
  position: absolute;
  left: 0;
  height: calc(6vh);
  bottom: 1rem;
}

.info-image {
  max-height: 50vh;
  height: 50vh;
}

.info-text {
  overflow: auto;
  padding-bottom: 2rem;
}
</style>
