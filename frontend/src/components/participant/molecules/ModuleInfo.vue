<template>
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
      v-for="keyword of openModuleInfoEntryDataList"
      :key="keyword"
    >
      <img
        :style="{ height: `${(gameHeight / 3) * 2}px` }"
        :src="`${imageDirectory}\\${keyword}.${fileExtension}`"
        :alt="keyword"
      />
      <div>
        {{ $t(`${translationPath}.${keyword}`) }}
      </div>
      <div>
        <el-button type="primary" @click="next">
          {{ $t('participant.molecules.moduleInfo.next') }}
        </el-button>
      </div>
    </el-carousel-item>
  </el-carousel>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Tutorial } from '@/types/api/Tutorial';
import * as tutorialService from '@/services/tutorial-service';
import * as cashService from '@/services/cash-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

export interface ModuleInfoEntryData {
  imageUrl: string;
  title: string;
  text: string;
}

@Options({
  components: {},
  emits: ['infoRead'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModuleInfo extends Vue {
  @Prop({ default: [] })
  readonly moduleInfoEntryDataList!: string[];
  @Prop({ default: '' }) readonly translationPath!: string;
  @Prop({ default: '' }) readonly imageDirectory!: string;
  @Prop({ default: 'jpg' }) readonly fileExtension!: string;
  @Prop({ default: 'moduleInfo' }) readonly infoType!: string;
  @Prop({ default: true }) readonly showTutorialOnlyOnce!: boolean;
  gameHeight = 0;
  tutorialSteps: Tutorial[] = [];

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

  mounted(): void {
    this.gameHeight = this.$el.parentElement.offsetHeight;
    tutorialService.registerGetList(
      this.updateTutorial,
      EndpointAuthorisationType.PARTICIPANT
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTutorial);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  updateTutorial(steps: Tutorial[]): void {
    this.tutorialSteps = steps;
    this.activeTabIndex = this.indexDelta;
    if (this.openModuleInfoEntryDataList.length === 0) {
      this.$emit('infoRead');
    }
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

  activeTabIndex = 0;
  carouselChanged(index: number): void {
    this.activeTabIndex = this.indexDelta + index;
  }

  next(): void {
    this.storeInfoStepRead();
    if (this.activeTabIndex + 1 === this.moduleInfoEntryDataList.length) {
      this.$emit('infoRead');
    } else {
      (this.$refs.carousel as any).next();
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
.el-carousel__item div {
  padding: 2rem;
}

img {
  width: 100%;
  object-fit: contain;
}
</style>
