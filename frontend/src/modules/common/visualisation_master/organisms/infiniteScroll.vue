<template>
  <div id="scrollVis">
    <div
      id="scrollParent"
      :style="{
        animation: `infiniteScroll ${
          fullScrollTime / timeModifier
        }s infinite linear`,
      }"
      :class="{ paused: paused }"
    >
      <div class="scroll-container columnLayout" id="scrollContainer" :style="{columnCount: columns}">
        <IdeaCard
          v-for="idea in useIdeas"
          :key="idea.id"
          class="scroll-item"
          :idea="idea"
          :is-editable="false"
          :allow-image-preview="false"
        />
      </div>
      <div class="scroll-container columnLayout" id="scrollContainer2" :style="{columnCount: columns}">
        <IdeaCard
          v-for="idea in useIdeas"
          :key="idea.id"
          class="scroll-item"
          :idea="idea"
          :is-editable="false"
          :allow-image-preview="false"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import {
  defaultFilterData,
  FilterData,
} from '@/components/moderator/molecules/IdeaFilterBase.vue';

@Options({
  components: {
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: 0 }) readonly timeModifier!: number;
  @Prop({ default: null }) readonly taskParameter!: any;
  @Prop({ default: [] }) readonly ideas!: Idea[];
  @Prop({ default: false }) readonly paused!: boolean;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  filter: FilterData = { ...defaultFilterData };

  useIdeas: Idea[] = [];

  fullScrollTime = 40;

  columns = 5;

  @Watch('ideas', { immediate: true })
  updateIdeas(): void {
    this.useIdeas = this.ideas;
    if (this.useIdeas.length > 10) {
      let repetitions = 1;
      while (this.useIdeas.length < 50) {
        this.useIdeas = this.useIdeas.concat(this.ideas);
        repetitions++;
      }
      if (repetitions === this.columns) {
        this.columns -= 1;
        console.log(this.columns);
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.el-carousel::v-deep(.el-carousel__item) {
  display: flex;
  justify-content: center;
  align-items: center;
}

.public-idea {
  max-width: 20rem;
}

.gallery-item {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  width: 20rem;
  max-height: 500px;
}

.el-carousel::v-deep(.el-carousel__mask) {
  background-color: unset;
}

.el-card {
  width: 100%;
  height: 100%;
}

.el-card::v-deep(.el-card__body) {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.el-card::v-deep(.card__text) {
  flex-basis: auto;
  flex-grow: 1;
  flex-shrink: 1;
  text-align: inherit;

  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
}

.scroll-container {
  overflow: hidden;
  white-space: nowrap;
  width: 100%;
  margin-top: 0.5rem;
}

.scroll-item {
  transition: all 0.5s;
}

@media only screen and (min-width: 950px) {
  .columnLayout {
    width: 100%;
    column-gap: 1rem;
    column-fill: balance;
  }
}

@media only screen and (max-width: 949px) {
  .columnLayout {
    width: 100%;
    column-width: 15rem;
    column-gap: 1rem;
    column-fill: balance;
  }
}

.playPause {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
  z-index: 1000;
  background-color: transparent;
  color: var(--color-dark-contrast);
  font-size: var(--font-size-xxlarge);
}

#scrollParent {
  width: 100%;
}

#scrollVis {
  width: 100%;
  height: 150%;
  overflow: hidden;
  position: relative;
  border-radius: var(--border-radius-small);
}
</style>
<style lang="scss">
@keyframes infiniteScroll {
  from {
    transform: translateY(0);
  }
  to {
    transform: translateY(-50%);
  }
}
.paused {
  animation-play-state: paused !important;
}
</style>
