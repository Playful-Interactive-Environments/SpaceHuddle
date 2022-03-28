<template>
  <div
    ref="scrollContainer"
    class="process-timeline-container"
    :style="{
      '--slider-steps': `${sliderSteps}`,
      '--slider-position': activeOnPublicScreen,
    }"
    @scroll="onScroll"
  >
    <div class="process-timeline" v-if="modelValue.length > 0 && !readonly">
      <div
        class="public-slider"
        v-if="hasPublicSlider"
        :style="{
          '--margin-side': `calc(100% / (${sliderSteps} * 2))`,
        }"
      >
        <TutorialStep
          :disableTutorial="readonly || modelValue.length < 2"
          step="changePublicScreen"
          :type="translationModuleName"
          :order="1"
          placement="bottom"
        >
          <el-slider
            v-if="sliderSteps - 1 > minPublicSliderCount"
            :max="sliderSteps - 1"
            v-model="activeOnPublicScreen"
            :format-tooltip="tooltip"
            :show-tooltip="false"
          ></el-slider>
        </TutorialStep>
      </div>
      <div class="media">
        <span
          class="media-left"
          :style="{
            width: canDisablePublicTimeline
              ? `calc(100% / (${sliderSteps}))`
              : 0,
          }"
        >
          <div
            class="timelineIcon"
            :class="{
              selected: getDBPublicIndex(activeOnPublicScreen) === -1,
            }"
          >
            <div
              v-if="hasPublicSlider"
              class="publicScreenView publicScreenViewDisabled"
              :class="{
                hide: getDBPublicIndex(activeOnPublicScreen) !== -1,
              }"
            >
              <font-awesome-icon :icon="['fac', 'presentation']" />
            </div>
            <span class="home" v-if="!useOtherPublicScreenTopic">
              <font-awesome-icon class="processIcon" icon="home" />
            </span>
            <span
              class="home useOtherPublicScreenTopic"
              @click="noPublicScreen"
              v-else
            >
              <span class="processIcon">
                <font-awesome-icon icon="home" />
                <span class="topicInfo">
                  {{ $t('moderator.organism.processTimeline.otherTopic') }}
                  {{ publicScreenTopic }}
                </span>
              </span>
            </span>
          </div>
          <i class="line"></i>
        </span>
        <el-steps
          class="media-content"
          :active="activeContentIndex"
          align-center
        >
          <draggable
            v-model="activePageDisplayContentList"
            tag="transition-group"
            :item-key="keyPropertyName"
            handle=".processIcon"
            @end="dragDone"
          >
            <template #item="{ element, index }">
              <el-step
                icon="-"
                :id="getKey(element)"
                :style="{
                  '--module-color': getContentListColor(element),
                  '--description-padding': hasParticipantToggle
                    ? '3rem'
                    : '12px',
                }"
              >
                <template #icon>
                  <div class="timelineIcon">
                    <div
                      v-if="hasPublicSlider"
                      class="publicScreenView"
                      :class="{
                        hide: getDBPublicIndex(activeOnPublicScreen) !== index,
                      }"
                    >
                      <font-awesome-icon :icon="['fac', 'presentation']" />
                    </div>
                    <TutorialStep
                      :disableTutorial="readonly || modelValue.length < 2"
                      step="changeOrder"
                      :type="translationModuleName"
                      :order="2"
                      placement="bottom"
                    >
                      <span @click="itemClicked(element)">
                        <font-awesome-icon
                          class="processIcon"
                          v-if="contentListIcon(element)"
                          :icon="contentListIcon(element)"
                        />
                        <span v-else class="processIcon withoutIcon">
                          {{ index }}
                        </span>
                      </span>
                    </TutorialStep>
                    <TutorialStep
                      v-if="hasParticipantToggle"
                      :disableTutorial="readonly"
                      step="activateParticipant"
                      :type="translationModuleName"
                      :order="4"
                      placement="bottom"
                    >
                      <div
                        class="participantView"
                        :class="{
                          'is-checked': isParticipantActive(element),
                          'no-module': !hasParticipantOption(element),
                        }"
                        v-on:click="timerContent = element"
                      >
                        <span class="time" v-if="isParticipantActive(element)">
                          {{ formattedTime(element) }}
                        </span>
                        <font-awesome-icon icon="mobile-screen-button" />
                      </div>
                    </TutorialStep>
                  </div>
                </template>
                <template #description>
                  <TutorialStep
                    v-if="isLinkedToDetails"
                    :disableTutorial="readonly || modelValue.length < 2"
                    step="selectItem"
                    :type="translationModuleName"
                    :order="3"
                    placement="bottom"
                  >
                    <span
                      class="link threeLineText"
                      v-on:click="itemClicked(element)"
                    >
                      {{ getTitle(element) }}
                    </span>
                  </TutorialStep>
                  <span v-else class="threeLineText">
                    {{ getTitle(element) }}
                  </span>
                </template>
              </el-step>
            </template>
          </draggable>
        </el-steps>
      </div>
      <TimerSettings
        v-if="showTimerSettings"
        v-model:showModal="showTimerSettings"
        :entity="getTimerEntity(timerContent)"
        :entityName="entityName"
        :defaultTimerSeconds="defaultTimerSeconds"
      />
    </div>
    <div
      class="process-timeline readonly"
      v-else-if="modelValue.length > 0 && readonly"
    >
      <el-steps :active="activeContentIndex" align-center class="readonly">
        <el-step
          icon="-"
          v-for="(element, index) in activePageDisplayContentList"
          :key="element.id"
        >
          <template #icon>
            <font-awesome-icon
              v-if="contentListIcon(element)"
              :icon="contentListIcon(element)"
              :style="{ color: getContentListColor(element) }"
            />
            <span v-else class="withoutIcon">{{ index }}</span>
          </template>
          <template #description>
            <span>
              {{ getTitle(element) }}
            </span>
          </template>
        </el-step>
      </el-steps>
    </div>
    <el-pagination
      v-if="pages.length > 1"
      :page-size="pageSize"
      :pager-count="11"
      layout="prev, pager, next"
      :total="modelValue.length"
      v-model:current-page="activePage"
    >
    </el-pagination>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import draggable from 'vuedraggable';
import TimerSettings from '@/components/moderator/organisms/settings/TimerSettings.vue';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as timerService from '@/services/timer-service';
import { TimerEntity } from '@/types/enum/TimerEntity';
import TaskStates from '@/types/enum/TaskStates';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';

@Options({
  components: {
    TutorialStep,
    draggable,
    TimerSettings,
  },
  emits: [
    'changeActiveElement',
    'changePublicScreen',
    'changeOrder',
    'update:publicScreen',
    'update:activeItem',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ProcessTimeline extends Vue {
  @Prop({ default: [] }) modelValue!: any[];
  @Prop({ default: null }) publicScreen!: any | null;
  @Prop({ default: null }) publicScreenTopic!: number | null;
  @Prop({ default: null }) activeItem!: any | null;
  @Prop({ default: 'processTimeline' }) readonly translationModuleName!: string;
  @Prop({ default: TimerEntity.TASK }) entityName!: string;
  @Prop({ default: false }) readonly readonly!: boolean;
  @Prop({ default: true }) readonly canDisablePublicTimeline!: boolean;
  @Prop({ default: true }) readonly isLinkedToDetails!: boolean;
  @Prop({ default: false }) readonly startParticipantOnPublicChange!: boolean;
  @Prop({ default: true }) readonly hasPublicSlider!: boolean;
  @Prop({ default: true }) readonly hasParticipantToggle!: boolean;
  @Prop({ default: 'id' }) readonly keyPropertyName!: string;
  @Prop({ default: null }) defaultTimerSeconds!: number | null;
  @Prop({ default: 'var(--color-primary)' }) readonly accentColor!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  @Prop({ default: (item) => true }) readonly hasParticipantOption!: (
    item: any
  ) => boolean;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  @Prop({ default: (item) => null }) readonly contentListColor!: (
    item: any
  ) => string | undefined;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  @Prop({ default: (item) => null }) readonly contentListIcon!: (
    item: any
  ) => string | undefined;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  @Prop({ default: (item) => '' }) readonly getKey!: (
    item: any
  ) => string | null;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  @Prop({ default: (item) => '' }) readonly getTitle!: (
    item: any
  ) => string | null;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  @Prop({ default: (item) => null }) readonly getTimerEntity!: (
    item: any
  ) => any | null;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  @Prop({ default: (a, b) => a === b }) readonly itemIsEquals!: (
    a: any,
    b: any
  ) => boolean;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  @Prop({ default: (item) => item }) readonly displayItem!: (item: any) => any;

  timerContent: any | null = null;
  activePage = 1;
  pageSize = 1000;
  pages: any[][] = [];

  get minPublicSliderCount(): number {
    return 0;
  }

  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  getContentListColor(item: any): string {
    if (this.contentListColor) {
      const color = this.contentListColor(item);
      if (color) return color;
    }
    return this.accentColor;
  }

  get sliderSteps(): number {
    if (this.canDisablePublicTimeline)
      return this.activePageContentList.length + 1;
    return this.activePageContentList.length;
  }

  oldScrollLeft = 0;
  onScroll(): void {
    const scrollContainer = this.$refs.scrollContainer as HTMLElement;
    this.oldScrollLeft = scrollContainer ? scrollContainer.scrollLeft : 0;
  }

  scrollParentToChild(parent: HTMLElement, child: HTMLElement): void {
    // Where is the parent on page
    const parentRect = parent.getBoundingClientRect();
    // What can you see?
    const parentViewableArea = {
      height: parent.clientHeight,
      width: parent.clientWidth,
    };

    // Where is the child
    const childRect = child.getBoundingClientRect();
    // Is the child viewable?
    const isViewable =
      childRect.left >= parentRect.left &&
      childRect.right <= parentRect.left + parentViewableArea.width;

    // if you can't see the child try to scroll parent
    if (!isViewable) {
      // Should we scroll using top or bottom? Find the smaller ABS adjustment
      const scrollLeft = childRect.left - parentRect.left;
      const scrollRight = childRect.right - parentRect.right;
      if (Math.abs(scrollLeft) < Math.abs(scrollRight)) {
        // we're near the top of the list
        parent.scrollLeft += scrollLeft;
      } else {
        // we're near the bottom of the list
        parent.scrollLeft += scrollRight;
      }
    }
  }

  @Watch('modelValue.length', { immediate: true })
  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      const pages: any[][] = [];
      for (let i = 0; i < this.modelValue.length / this.pageSize; i++) {
        pages[i] = [];
      }
      this.modelValue.forEach((item, index) => {
        pages[Math.floor(index / this.pageSize)].push(item);
        if (item == this.activeItem) {
          this.activePage = Math.floor(index / this.pageSize) + 1;
        }
      });
      this.pages = pages;
    }
    const scrollContainer = this.$refs.scrollContainer as HTMLElement;
    if (scrollContainer) {
      if (this.oldScrollLeft !== 0)
        scrollContainer.scrollTo(this.oldScrollLeft, 0);
      else if (this.activeItem) {
        const activeKey = this.getKey(this.activeItem);
        if (activeKey) {
          const activeDom = document.getElementById(activeKey);
          if (activeDom) {
            this.scrollParentToChild(scrollContainer, activeDom);
          }
        }
      }
    }
  }

  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  isParticipantActive(item: any): boolean {
    return timerService.isActive(this.getTimerEntity(item));
  }

  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  changeParticipant(item: any, active: boolean): void {
    if (active) this.timerContent = item;
    else {
      timerService.setState(item, TaskStates.WAIT);
      timerService.update(this.entityName, item.id, item);
    }
  }

  get showTimerSettings(): boolean {
    return this.timerContent !== null;
  }

  set showTimerSettings(value: boolean) {
    if (!value) this.timerContent = null;
  }

  get activeContentIndex(): number {
    if (this.activePageContentList && this.activeItem) {
      const index = this.activeItem
        ? this.activePageContentList.findIndex((item) =>
            this.itemIsEquals(item, this.activeItem)
          )
        : -1;
      if (index > -1) return index;
    }
    return 0;
  }

  get activePageContentList(): any[] {
    if (this.pages.length > 0 && this.pages.length >= this.activePage)
      return this.pages[this.activePage - 1];
    return [];
  }

  set activePageContentList(items: any[]) {
    if (this.pages.length > 0 && this.pages.length >= this.activePage)
      this.pages[this.activePage - 1] = items;
  }

  get activePageDisplayContentList(): any[] {
    return this.activePageContentList.map((item) => this.displayItem(item));
  }

  set activePageDisplayContentList(items: any[]) {
    const newList: any[] = [];
    items.forEach((item, index) => {
      newList[index] = this.getContentItem(this.getKey(item));
    });
    this.activePageContentList = newList;
  }

  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  getContentItem(key: any): any {
    return this.modelValue.find(
      (item) => this.getKey(this.displayItem(item)) === key
    );
  }

  dragDone(): void {
    this.$emit('changeOrder', this.activePageContentList);
  }

  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  itemClicked(item: any): void {
    const result = this.getContentItem(this.getKey(item));
    this.$emit('update:activeItem', result);
    this.$emit('changeActiveElement', result);
  }

  tooltip(): string {
    return (this as any).$t(
      `moderator.organism.${this.translationModuleName}.changePublicScreen`
    );
  }

  noPublicScreen(): void {
    this.activeOnPublicScreen = 0;
  }

  get useOtherPublicScreenTopic(): boolean {
    return (
      this.getDBPublicIndex(this.activeOnPublicScreen) === -1 &&
      !!this.publicScreen
    );
  }

  get activeOnPublicScreen(): number {
    let index = this.publicScreen
      ? this.activePageContentList.findIndex((item) =>
          this.itemIsEquals(item, this.publicScreen)
        )
      : -1;
    return this.getSliderPublicIndex(index);
  }

  set activeOnPublicScreen(index: number) {
    if (this.sliderSteps > index) {
      index = this.getDBPublicIndex(index);
      const publicItem = this.activePageContentList[index];
      if (this.publicScreen !== publicItem) {
        if (this.publicScreen && this.startParticipantOnPublicChange) {
          this.timerContent = publicItem;
        }
        this.$emit('update:publicScreen', publicItem);
        this.$emit('changePublicScreen', publicItem);
      }
    }
  }

  getDBPublicIndex(index: number): number {
    if (this.canDisablePublicTimeline) return index - 1;
    return index;
  }

  getSliderPublicIndex(index: number): number {
    if (this.canDisablePublicTimeline) return index + 1;
    return index;
  }

  formattedTime(item: any | null): string {
    if (item !== null) {
      const timeLeft = timerService.getRemainingTime(this.getTimerEntity(item));
      if (timeLeft) {
        let minutes = Math.floor(timeLeft / 60);
        let seconds = timeLeft - minutes * 60;
        return `${minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
      }
    }
    return 'Ꝏ';
  }

  interval!: any;
  readonly intervalTime = 1000;
  startTimer(): void {
    clearInterval(this.interval);
    this.interval = setInterval(() => this.refreshTimer(), this.intervalTime);
  }

  refreshTimer(): void {
    this.modelValue.forEach((item) => {
      if (this.hasParticipantOption(this.displayItem(item))) {
        const timerEntity = this.getTimerEntity(item);
        let remainingTime = timerService.getRemainingTime(timerEntity);
        const state = timerService.getState(timerEntity);
        if (remainingTime !== null && remainingTime > 0) {
          remainingTime -= 1;
          if (remainingTime == 0) {
            this.changeParticipant(timerEntity, false);
          }
          timerService.setRemainingTime(timerEntity, remainingTime);
        } else if (state == TaskStates.ACTIVE && remainingTime == 0) {
          this.changeParticipant(timerEntity, false);
        }
      }
    });
  }

  mounted(): void {
    this.startTimer();
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.public-slider {
  display: flex;
  position: relative;
}

.publicScreenView,
.participantView,
.publicScreenViewDisabled {
  color: white;
  height: 3.7rem;
  width: 1.9rem;
  --tag-border-radius: 0.4rem;
  margin: auto;
  font-size: 1rem;
  position: absolute;
  z-index: 100;
  cursor: pointer;
}

.publicScreenView,
.participantView {
  background-color: var(--module-color);
  left: 50%;
  transform: translate(-50%, 0);
}

.publicScreenView {
  pointer-events: none;
  top: -2.5rem;
  border-radius: var(--tag-border-radius) var(--tag-border-radius) 0 0;
  border-bottom-width: 0;
  padding-top: 0.2rem;
}

.publicScreenViewDisabled {
  height: 2.5rem;
  pointer-events: none;
  text-align: center;
  background-color: var(--color-background-gray);
  border-radius: var(--tag-border-radius) var(--tag-border-radius) 0 0;
  border-width: 2px;
  border-style: dashed;
  border-color: var(--color-gray-inactive);
  color: var(--color-gray-inactive);

  svg {
    padding-top: 0.2rem;
  }
}

.participantView {
  background-color: var(--color-background-gray);
  border-radius: 0 0 var(--tag-border-radius) var(--tag-border-radius);
  border-width: 2px;
  border-style: dashed;
  border-color: var(--color-gray-inactive);
  border-top-width: 0;
  padding-top: 2.3rem;
  display: flex;
  flex-direction: column;
  color: var(--color-gray-inactive);

  &.is-checked {
    border-width: 0;
    color: white;
    border-color: var(--color-primary);
    background-color: var(--module-color);
  }

  .time {
    border-radius: var(--tag-border-radius);
    padding: 0 0.1rem;
    font-size: 0.7rem;
    background-color: var(--module-color);
    position: absolute;
    left: 50%;
    top: 1.4rem;
    transform: translate(-50%, 0);
  }
}

.processIcon {
  cursor: grab;
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  z-index: 1000;
  background-color: var(--color-background-gray);
  border-radius: var(--border-radius-xs);
  border: 2px solid var(--color-primary);
  padding: 0.4rem;
  aspect-ratio: 1 / 1;
  margin: -0.2rem 0;
  color: var(--module-color);
}

.el-pagination::v-deep {
  text-align: center;
}

.no-module {
  visibility: hidden;
}

.public-screen-slider {
  background-color: aqua;
  display: inline-flex;
  flex-direction: row;
  justify-content: space-between;
  width: 100%;
  height: 100%;
}

.el-steps::v-deep {
  .el-step__line {
    background-color: var(--color-primary);
  }

  .el-step__icon {
    background: var(--color-background-gray);
  }

  .el-step__title {
    margin-top: 0.5rem;
    &.is-process,
    &.is-finish {
      color: inherit;
    }
  }

  .is-icon {
    width: 40px;
    font-size: var(--font-size-large);
  }

  .is-process {
    font-weight: var(--font-weight-bold);
    .is-icon {
      width: 55px;
      font-size: var(--font-size-xxlarge);

      .processIcon {
        background-color: var(--color-primary);
      }

      .withoutIcon {
        color: white;
      }
    }
  }

  .el-step__description {
    padding-top: var(--description-padding);
    line-height: 0.8rem;

    &.is-wait {
      color: var(--color-primary);
    }
  }

  .el-step.is-center {
    .el-step__description {
      padding-left: 10%;
      padding-right: 10%;
    }
  }

  &.readonly {
    .el-step__icon {
      cursor: unset;
    }

    .is-icon {
      width: 25px;
      font-size: var(--font-size-large);
    }

    .el-step__description {
      padding-top: 12.5px;

      &.is-process {
        padding-top: 0;
      }
    }

    .is-wait img {
      -webkit-filter: grayscale(1); /* Webkit */
      filter: gray; /* IE6-9 */
      filter: grayscale(1); /* W3C */
    }

    .is-process {
      font-weight: var(--font-weight-bold);
      .is-icon {
        width: 50px;
        font-size: var(--font-size-xxxlarge);
      }
    }

    .is-finish {
    }
  }
}

.el-slider::v-deep {
  --el-slider-runway-bg-color: var(--color-gray-dark);
  margin: 0.3rem var(--margin-side);

  .el-slider__button {
    width: 1rem;
    height: 1rem;
    display: none;
  }

  .el-slider__bar {
    --height: 0.5rem;
    --background_color: var(--color-primary);
    background-image: linear-gradient(
        315deg,
        var(--background_color) 25%,
        transparent 25%
      ),
      linear-gradient(225deg, var(--background_color) 25%, transparent 25%);
    background-position-x: 0, 0;
    background-position-y: calc(var(--height) / 2), calc(var(--height) / 2);
    background-size: var(--height) var(--height);
    background-color: var(--color-background-gray);
    height: var(--height);
  }

  .el-slider__runway {
    --height: 0.5rem;
    --background_color: var(--color-primary);
    background-image: linear-gradient(
        135deg,
        var(--background_color) 25%,
        transparent 25%
      ),
      linear-gradient(45deg, var(--background_color) 25%, transparent 25%);
    background-position-y: calc(var(--height) / 2), calc(var(--height) / 2);
    background-size: var(--height) var(--height);
    --step-size: calc(100% / (var(--slider-steps) - 1));
    --slider-marker: calc(var(--step-size) * var(--slider-position));
    --color-none: #{rgba(#000, 0.2)};
    --mask: linear-gradient(
      90deg,
      var(--color-none) 0%,
      var(--color-none) calc(var(--slider-marker) - var(--step-size)),
      red var(--slider-marker),
      var(--color-none) calc(var(--slider-marker) + var(--step-size)),
      var(--color-none) 100%
    );
    -webkit-mask: var(--mask);
    mask: var(--mask);
    background-color: var(--color-background-gray);
    height: var(--height);
  }
}

.is-process {
  .withoutIcon {
    height: 2.9rem;
  }
}

.withoutIcon {
  border-style: solid;
  border-width: 2px;
  height: 2.1rem;
  display: inline-flex;
  text-align: center;
  justify-content: center;
  align-items: center;
  flex-shrink: 0;
  aspect-ratio: 1;
  padding: initial;
  font-weight: var(--font-weight-bold);
  background-color: white;
}

.process-timeline-container {
  overflow-x: auto;
  overflow-y: visible;
  scrollbar-color: var(--color-primary) var(--color-gray);
  scrollbar-width: thin;
  padding-bottom: 0.5rem;

  .readonly {
    margin-top: 0.5rem;
  }
}

.process-timeline {
  min-width: calc(var(--slider-steps) * 5rem);
}

.media-left {
  margin-right: 0;
  text-align: center;
  align-items: center;
  position: relative;

  .timelineIcon {
    margin-top: 1.01rem;
  }

  .is-process {
    font-size: var(--font-size-xxxlarge);

    .processIcon {
      background-color: var(--color-primary);
      color: white;
    }
  }

  .home {
    position: relative;
    margin-top: 2rem;
    line-height: 0.8;

    .processIcon {
      padding: unset;
      background-color: var(--color-primary);

      svg {
        padding: 0.4rem;
        background-color: var(--color-background-gray);
        border-radius: calc(var(--border-radius-xs) - 2px);
      }
    }

    svg.processIcon {
      padding: 0.4rem;
      background-color: var(--color-background-gray);
    }
  }

  .useOtherPublicScreenTopic {
    .topicInfo {
      color: white;
      font-size: 6pt;
      white-space: nowrap;
      line-height: 0.4;
    }
  }
}

.line {
  background-color: var(--color-primary);
  position: absolute;
  height: 2px;
  top: 11px;
  left: calc(50% + 1.3rem);
  right: -50%;
}
</style>
