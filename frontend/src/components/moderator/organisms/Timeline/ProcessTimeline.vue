<template>
  <div>
    <div
      class="process-timeline"
      :class="{ 'process-timeline__vertical': direction === 'vertical' }"
      v-if="modelValue.length > 0 && !readonly"
    >
      <div
        :class="{ media: !isVertical, stretch: isVertical }"
        :style="{ 'grid-template-rows': `1fr ${modelValue.length * 2 - 1}fr` }"
      >
        <el-tooltip
          :class="{ 'no-module': !canDisablePublicTimeline }"
          placement="top-start"
          :content="
            $t(
              `moderator.organism.${translationModuleName}.activatePublicScreen`
            )
          "
        >
          <el-switch
            v-model="usePublicScreen"
            :class="{ 'media-left': !isVertical }"
            :style="{
              width: isVertical
                ? 'auto'
                : `calc(100% / (${activePageContentList.length} * 2))`,
            }"
          />
        </el-tooltip>
        <el-slider
          class="media-content"
          v-if="activePageContentList.length > 0"
          :disabled="!usePublicScreen"
          :max="activePageContentList.length - 1"
          v-model="activeOnPublicScreen"
          :vertical="isVertical"
          :style="{
            margin: isVertical
              ? '0.3rem' //`0.3rem 0.3rem 2.3rem 0.3rem`
              : `0.3rem calc(100% / (${activePageContentList.length} * 2)) 0.3rem 0rem`,
          }"
          :format-tooltip="tooltip"
          :height="isVertical ? `100%` : ''"
        ></el-slider>
      </div>
      <el-steps
        :direction="direction"
        :active="activeContentIndex"
        align-center
      >
        <draggable
          v-model="activePageContentList"
          tag="transition-group"
          :item-key="keyPropertyName"
          handle=".el-step__head"
          @end="dragDone"
        >
          <template #item="{ element, index }">
            <el-step icon="-" :id="getKey(element)">
              <template #icon>
                <el-tooltip
                  placement="top"
                  :content="
                    $t(
                      `moderator.organism.${translationModuleName}.changeOrder`
                    )
                  "
                >
                  <img
                    v-if="contentListIcon(element)"
                    :src="contentListIcon(element)"
                    alt=""
                  />
                  <span v-else class="circle">{{ index }}</span>
                </el-tooltip>
              </template>
              <template #title>
                <el-badge
                  :value="formattedTime(element)"
                  :hidden="!isParticipantActive(element)"
                  :class="{ 'no-module': !hasParticipantOption(element) }"
                >
                  <el-tooltip
                    placement="top"
                    :content="
                      $t(
                        `moderator.organism.${translationModuleName}.activateParticipant`
                      )
                    "
                  >
                    <el-button
                      :class="{ 'is-checked': isParticipantActive(element) }"
                      v-on:click="timerContent = element"
                      circle
                    >
                      <font-awesome-icon icon="mobile" />
                    </el-button>
                  </el-tooltip>
                </el-badge>
              </template>
              <template #description>
                <el-tooltip
                  placement="top"
                  :content="
                    $t(`moderator.organism.${translationModuleName}.selectItem`)
                  "
                  v-if="isLinkedToDetails"
                >
                  <span class="link" v-on:click="itemClicked(element)">
                    {{ getTitle(element) }}
                  </span>
                </el-tooltip>
                <span v-else>
                  {{ getTitle(element) }}
                </span>
              </template>
            </el-step>
          </template>
        </draggable>
      </el-steps>
      <TimerSettings
        v-if="showTimerSettings"
        v-model:showModal="showTimerSettings"
        :entity="getTimerEntity(timerContent)"
        :entityName="entityName"
        :defaultTimerSeconds="defaultTimerSeconds"
      />
    </div>
    <div
      class="process-timeline"
      :class="{ 'process-timeline__vertical': direction === 'vertical' }"
      v-else-if="modelValue.length > 0 && readonly"
    >
      <el-steps
        :direction="direction"
        :active="activeContentIndex"
        align-center
        class="readonly"
      >
        <el-step
          icon="-"
          v-for="(element, index) in activePageContentList"
          :key="element.id"
        >
          <template #icon>
            <img
              v-if="contentListIcon(element)"
              :src="contentListIcon(element)"
              alt=""
            />
            <font-awesome-icon v-else icon="circle">{{
              index
            }}</font-awesome-icon>
          </template>
          <template #description>
            <span class="link" v-on:click="itemClicked(element)">
              {{ element.name }}
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
      :class="{ 'is-vertical': direction === 'vertical' }"
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

@Options({
  components: {
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
  @Prop({ default: null }) activeItem!: any | null;
  @Prop({ default: 'processTimeline' }) readonly translationModuleName!: string;
  @Prop({ default: TimerEntity.TASK }) entityName!: string;
  @Prop({ default: 'horizontal' }) readonly direction!: string;
  @Prop({ default: false }) readonly readonly!: boolean;
  @Prop({ default: true }) readonly canDisablePublicTimeline!: boolean;
  @Prop({ default: true }) readonly isLinkedToDetails!: boolean;
  @Prop({ default: false }) readonly startParticipantOnPublicChange!: boolean;
  @Prop({ default: 'id' }) readonly keyPropertyName!: string;
  @Prop({ default: null }) defaultTimerSeconds!: number | null;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  @Prop({ default: (item) => true }) readonly hasParticipantOption!: (
    item: any
  ) => boolean;
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  @Prop({ default: (item) => null }) readonly contentListIcon!: (
    item: any
  ) => string | null;
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

  timerContent: any | null = null;
  activePage = 1;
  pageSize = 10;
  pages: any[][] = [];

  @Watch('modelValue.length', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      for (let i = 0; i < this.modelValue.length / this.pageSize; i++) {
        this.pages[i] = [];
      }
      this.modelValue.forEach((item, index) => {
        this.pages[Math.floor(index / this.pageSize)].push(item);
        if (item == this.activeItem) {
          this.activePage = Math.floor(index / this.pageSize) + 1;
        }
      });
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

  get isVertical(): boolean {
    return this.direction === 'vertical';
  }

  get activeContentIndex(): number {
    if (this.activePageContentList && this.activeItem) {
      const index = this.activePageContentList.findIndex(
        (item) => item == this.activeItem
      );
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

  dragDone(): void {
    this.$emit('changeOrder', this.activePageContentList);
  }

  // eslint-disable-next-line @typescript-eslint/explicit-module-boundary-types
  itemClicked(item: any): void {
    this.$emit('update:activeItem', item);
    this.$emit('changeActiveElement', item);
  }

  tooltip(): string {
    return (this as any).$t(
      `moderator.organism.${this.translationModuleName}.changePublicScreen`
    );
  }

  get usePublicScreen(): boolean {
    return this.activeOnPublicScreen > -1 || !this.canDisablePublicTimeline;
  }

  set usePublicScreen(use: boolean) {
    if (use && this.activePageContentList.length > 0) {
      this.$emit('update:publicScreen', this.activePageContentList[0]);
      this.$emit('changePublicScreen', this.activePageContentList[0]);
    } else if (!use) {
      this.$emit('update:publicScreen', null);
      this.$emit('changePublicScreen', null);
    }
  }

  get activeOnPublicScreen(): number {
    const index = this.activePageContentList.findIndex(
      (item) => item === this.publicScreen
    );
    if (this.isVertical && index > -1)
      return this.activePageContentList.length - 1 - index;
    return index;
  }

  set activeOnPublicScreen(index: number) {
    if (this.usePublicScreen) {
      if (this.isVertical)
        index = this.activePageContentList.length - 1 - index;
      if (this.activePageContentList.length > index) {
        const publicItem = this.activePageContentList[index];
        if (this.publicScreen !== publicItem) {
          if (this.publicScreen && this.startParticipantOnPublicChange) {
            this.timerContent = publicItem;
          }
          this.$emit('update:publicScreen', publicItem);
        }
      }
    }
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
      const timerEntity = this.getTimerEntity(item);
      let remainingTime = timerService.getRemainingTime(timerEntity);
      const state = timerService.getState(timerEntity);
      if (remainingTime !== null && remainingTime > 0) {
        remainingTime -= 1;
        if (remainingTime == 0) {
          this.changeParticipant(timerEntity, false);
        } else {
          timerService.setRemainingTime(timerEntity, remainingTime);
        }
      } else if (state == TaskStates.ACTIVE && remainingTime == 0) {
        this.changeParticipant(timerEntity, false);
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
.el-pagination::v-deep {
  text-align: center;

  &.is-vertical {
    button:enabled,
    li {
      color: white;
    }

    .active {
      color: var(--color-purple);
    }
  }
}

.no-module {
  visibility: hidden;
}

.media-left {
  margin-right: unset;
  margin-top: auto;
  margin-bottom: auto;
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
  .el-step__icon {
    background: var(--color-background-gray);
    cursor: grab;
  }

  .el-step__title {
    margin-top: 0.5rem;
    &.is-process,
    &.is-finish {
      color: inherit;
    }
  }

  &.readonly {
    .is-icon {
      width: 25px;
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
      }
    }

    .is-finish {
    }
  }
}

.el-slider::v-deep {
  .el-slider__button {
    mask-image: url('~@/assets/icons/svg/public-screen.svg');
    mask-repeat: no-repeat;
    mask-position: center;
    mask-size: contain;
    background-color: white;
    border-width: 4px 5px 8px 4px;
    border-color: var(--color-primary);
    border-radius: unset;
    width: calc(var(--el-slider-button-size) + 4px);
  }
}

.el-badge::v-deep {
  //margin-right: 0.7rem;
}

.el-button::v-deep {
  &.is-circle {
    padding: 0;
    color: var(--color-gray-inactive);
    background-color: unset;
    border: unset;
    font-size: 20pt;
    min-height: unset;
    min-width: 25px;

    &.is-checked {
      color: var(--color-primary);
    }
  }
}

.el-badge::v-deep {
  .el-badge__content--primary {
    right: calc(5px + var(--el-badge-size) / 2);
  }
}

.process-timeline {
  &__vertical {
    display: flex;

    .stretch {
      position: relative;
      top: -2rem;
      display: inline-flex;
      flex-direction: column;
      gap: 1rem;
    }

    .el-switch::v-deep.is-checked .el-switch__core {
      border-color: var(--color-darkblue-light);
      background-color: var(--color-darkblue-light);
    }

    .el-step::v-deep {
      .el-step__icon {
        background-color: var(--color-primary);
      }

      .el-step__title {
        margin-top: unset;
      }

      .el-step__main {
        display: flex;

        .el-step__description {
          padding: 0.6rem;

          &.is-process,
          &.is-finish {
            color: white;
          }
        }
      }
    }

    .el-slider::v-deep {
      .el-slider__button {
        background-color: var(--color-primary);
        border-color: white;
      }

      .el-slider__runway.disabled .el-slider__button {
        border-color: var(--el-slider-disable-color);
      }

      .el-slider__bar {
        background-color: var(--el-slider-runway-background-color);
      }

      .el-slider__runway {
        background-color: white;
      }
    }

    .el-button::v-deep {
      &.is-circle.is-checked {
        color: white;
      }
    }

    .el-badge::v-deep {
      .el-badge__content--primary {
        background-color: white;
        color: var(--color-primary);
        border-color: var(--color-primary);
      }
    }
  }
}

.circle {
  border-radius: 50%;
  border-style: solid;
  border-width: 2px;
  display: inline-flex;
  text-align: center;
  justify-content: center;
  align-items: center;
  aspect-ratio: 1;
  flex-shrink: 0;
  height: 1.5rem;
  font-weight: var(--font-weight-bold);
}
</style>
