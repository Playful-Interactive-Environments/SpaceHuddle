<template>
  <div
    ref="parentElement"
    class="parallelCoordinatesContainer"
    style="position: relative"
  >
    <div class="participationContainer">
      <div
        class="axisControls axisControlsFlex"
        v-for="axis in activeAxes"
        :key="axis.taskData.taskId"
        :style="{
          textAlign: 'center',
          width: `${axesSpacing}px`,
          gap: `${axesSpacing / 4}px`,
          paddingLeft: `${axesSpacing / 3.5}px`,
        }"
      >
        <div class="listButton">
          <el-dropdown
            trigger="click"
            placement="bottom"
            v-if="isIdeaTask(axis)"
            :max-height="height"
            :hide-on-click="false"
          >
            <div class="el-dropdown-link">
              <font-awesome-icon icon="list" class="listIcon" />
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item
                  v-for="entry of getCachedIdeas(axis)"
                  :key="entry.avatar.id + axis.taskData.taskId"
                  :divided="true"
                  :disabled="true"
                  :style="{
                    cursor: 'default',
                    flexDirection: 'column',
                    alignItems: 'flex-start',
                    pointerEvents: 'none',
                    backgroundColor: selectedParticipantIds.includes(
                      entry.avatar.id
                    )
                      ? 'var(--color-background-blue)'
                      : 'transparent',
                  }"
                >
                  <div
                    class="dropDownParticipantIconContainer"
                    v-if="entry.avatar"
                  >
                    <font-awesome-icon
                      :icon="entry.avatar.symbol"
                      :style="{ color: entry.avatar.color }"
                    ></font-awesome-icon>
                  </div>
                  <div class="dropDownIdeaContainer">
                    <div
                      class="ideaCardContainer"
                      v-for="(idea, index) of entry.ideas"
                      :key="
                        entry.avatar.id + axis.taskData.taskId + (idea ? idea.id : index)
                      "
                    >
                      <IdeaCard
                        v-if="idea"
                        class="IdeaCard"
                        :idea="idea"
                        :is-selectable="false"
                        :is-editable="false"
                        :cutLongTexts="true"
                        :portrait="!(idea.link || idea.image)"
                      />
                    </div>
                  </div>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
        <p class="participantCount">
          <font-awesome-icon icon="user" /> {{ getParticipationCount(axis) }}
        </p>
      </div>
    </div>

    <svg
      ref="svgElement"
      class="parallelSVG"
      :style="{ paddingLeft: `${paddingLeft}px` }"
    >
      <!-- Average Data Line -->
      <g
        v-for="(pathPart, index) in averageDataLinePath"
        :key="pathPart?.path"
        :fill="'var(--color-evaluating)'"
      >
        <path
          v-if="pathPart"
          class="dataLine averageDataLine"
          :d="pathPart.path"
          fill="none"
        />
        <g
          class="aboveBelow"
          v-if="getAboveBelow(pathPart?.value, index, true) > 0"
          :transform="`translate(${pathPart?.x + 7}, ${pathPart?.y + 5})`"
        >
          <path
            :transform="`translate(0, 0) scale(0.025)`"
            :d="getIconDefinition('angle-down').icon[4] as string"
          />
          <text font-size="12" :transform="`translate(10, 18)`">
            {{ getAboveBelow(pathPart.value, index, true) }}
          </text>
        </g>

        <g
          class="aboveBelow"
          v-if="getAboveBelow(pathPart?.value, index) > 0"
          :transform="`translate(${pathPart?.x + 7}, ${pathPart?.y - 20})`"
        >
          <path
            :transform="`scale(0.025)`"
            :d="getIconDefinition('angle-up').icon[4] as string"
          />
          <text font-size="12" :transform="`translate(10, 0)`">
            {{ getAboveBelow(pathPart.value, index) }}
          </text>
        </g>
      </g>

      <!-- Data Lines as Curves -->
      <g v-for="entry in chartData" :key="entry.participant.id">
        <VariableSVGWrapper>
          <template
            v-slot="{
              dataLine = this.getDataLine(entry),
              participantColor = entry.participant.avatar.color,
            }"
          >
            <g
              v-for="pathPart in dataLine"
              :key="pathPart?.path"
              class="dataLineHover"
              fill="none"
              @mouseenter="
                setHoverStroke(
                  participantColor,
                  entry.participant.id,
                  participantColor
                )
              "
              @mouseleave="
                setHoverStroke(
                  'var(--color-dark-contrast)',
                  entry.participant.id,
                  participantColor
                )
              "
              @click="participantSelectionChanged(entry.participant.id)"
            >
              <path v-if="pathPart" :d="pathPart.path" />
            </g>
            <g
              v-for="(pathPart, index) in dataLine"
              :key="pathPart?.path"
              class="dataLine"
              :class="entry.participant.id"
              fill="none"
              :style="{
                strokeWidth: selectedParticipantIds.includes(
                  entry.participant.id
                )
                  ? '3px'
                  : '',
                stroke: selectedParticipantIds.includes(entry.participant.id)
                  ? participantColor
                  : 'var(--color-dark-contrast)',
              }"
            >
              <path
                class="participantDataLineIcon"
                v-if="index === dataLine?.length - 1"
                :transform="`translate(${pathPart?.x + 5}, ${
                  pathPart?.y - 10
                }), scale(0.04)`"
                :d="getIconDefinition(entry.participant.avatar.symbol).icon[4] as string"
                :fill="entry.participant.avatar.color"
                :style="{
                  opacity: selectedParticipantIds.includes(entry.participant.id)
                    ? 1
                    : 0,
                }"
              />
              <circle
                class="circle"
                :cx="pathPart?.x"
                :cy="pathPart?.y"
                :r="0"
              />
              <path
                v-if="pathPart"
                class="dataLinePathSegment"
                :class="{ dashed: pathPart.dashed }"
                :d="pathPart.path"
                :stroke-dasharray="pathPart.dashed ? '4,4' : '0'"
                :style="{
                  opacity: pathPart?.dashed
                    ? '20%'
                    : selectedParticipantIds.includes(entry.participant.id)
                    ? '1'
                    : '45%',
                }"
              />
            </g>
          </template>
        </VariableSVGWrapper>
      </g>
      <!-- Axes -->
      <g
        class="axes"
        v-for="(axis, index) in activeAxes"
        :key="axis.taskData.taskId"
        :transform="`translate(${axesSpacing * index + paddingLeft}, 0)`"
      >
        <!-- Axis Line -->
        <line
          :y1="padding"
          :y2="height - padding"
          stroke-width="2"
          stroke-linecap="round"
        />

        <!-- Tick Marks and Labels for the active sub-axis -->
        <g
          v-for="labelIndex in this.getLabelCount(
            axis.axisValues.find((ax) => ax?.id === axis.categoryActive)?.range
          ) + 1"
          :key="labelIndex - 1 + axis.taskData.taskId"
        >
          <VariableSVGWrapper>
            <template
              v-slot="{
                labelPosition = this.getLabelPosition(
                  labelIndex - 1,
                  this.getLabelCount(
                    axis.axisValues.find((ax) => ax?.id === axis.categoryActive)
                      ?.range
                  )
                ),
              }"
            >
              <line
                :y1="labelPosition"
                :y2="labelPosition"
                :x1="-5"
                :x2="5"
                stroke-width="2"
                stroke-linecap="round"
              />
              <text
                :y="labelPosition - 6"
                x="0"
                font-size="10.5"
                class="axisLabel"
                text-anchor="middle"
                :style="{
                  stroke: 'var(--color-background)',
                  strokeWidth: '5pt',
                  paintOrder: 'stroke',
                  textAlign: 'center',
                }"
              >
                {{
                  Math.round(
                    ((axis.axisValues.find(
                      (value) => value.id === axis.categoryActive
                    ).range /
                      this.getLabelCount(
                        axis.axisValues.find(
                          (ax) => ax?.id === axis.categoryActive
                        )?.range
                      )) *
                      (labelIndex - 1) +
                      Number.EPSILON) *
                      100
                  ) / 100
                }}
              </text>
            </template>
          </VariableSVGWrapper>
        </g>
      </g>
    </svg>

    <!-- Dropdown Menus -->
    <div class="controls">
      <div class="axisControlsContainer">
        <div
          class="axisControls"
          v-for="(axis, index) in activeAxes"
          :key="axis.taskData.taskId"
          :style="{
            textAlign: 'center',
            width: `${axesSpacing / 1.5}px`,
            margin: `0 ${axesSpacing / 6}px`,
          }"
          draggable="true"
          @dragstart="onDragStart(index)"
          @dragover.prevent
          @drop="onDrop(index)"
        >
          <div class="axisSelections">
            <div class="column">
              <el-dropdown
                v-on:command="updateSubAxis(index, $event)"
                trigger="click"
                placement="bottom"
                :style="{ opacity: axis.axisValues.length > 1 ? '1' : '0' }"
                :disabled="axis.axisValues.length <= 1"
              >
                <div class="el-dropdown-link cogButton">
                  <font-awesome-icon icon="cog" />
                </div>
                <template #dropdown>
                  <el-dropdown-menu>
                    <el-dropdown-item
                      v-for="(subAxis, subAxisIndex) in axis.axisValues"
                      :key="subAxis ? subAxis.id : subAxisIndex"
                      :command="subAxis ? subAxis.id : null"
                    >
                      {{
                        subAxis
                          ? $t(getTranslationPath(axis) + subAxis.id)
                          : 'N/A'
                      }}
                    </el-dropdown-item>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
            <div class="column columnCenter">
              <el-dropdown
                v-if="availableAxes.length > 1"
                v-on:command="activateAxis($event, index, true)"
                trigger="click"
                placement="bottom"
                :disabled="
                  availableAxes.filter((avAxis) => !this.axes.includes(avAxis))
                    .length < 1
                "
              >
                <div class="el-dropdown-link">
                  <font-awesome-icon
                    class="axisIcon"
                    :icon="getIconOfAxis(axis)"
                    :style="{
                      color: getColorOfAxis(axis),
                    }"
                  />
                </div>
                <template #dropdown>
                  <el-dropdown-menu class="centered-dropdown-menu">
                    <template
                      v-for="(ax, axIndex) in availableAxesForSelection"
                      :key="ax ? ax.taskData.taskId + 'ax' : axIndex + 'ax'"
                    >
                      <el-dropdown-item
                        v-if="
                          (availableAxesForSelection[axIndex - 1] &&
                            ax.taskData.topicOrder !==
                              availableAxesForSelection[axIndex - 1].taskData
                                .topicOrder) ||
                          axIndex === 0
                        "
                        class="heading oneLineText"
                        :divided="true"
                        :style="{
                          pointerEvents: 'none',
                          paddingBottom: '0.02rem',
                          paddingTop: '0.02rem',
                        }"
                        disabled
                      >
                        {{ ax.taskData.topicName }}
                      </el-dropdown-item>
                      <el-dropdown-item
                        :command="ax ? ax : null"
                        :divided="
                          (availableAxesForSelection[axIndex - 1] &&
                            ax.taskData.topicOrder !==
                              availableAxesForSelection[axIndex - 1].taskData
                                .topicOrder) ||
                          axIndex === 0
                        "
                      >
                        <div class="centered-dropdown-item">
                          <font-awesome-icon
                            class="axisIcon"
                            :icon="getIconOfAxis(ax)"
                            :style="{
                              color: getColorOfAxis(ax),
                            }"
                          />
                          &nbsp;{{ ax ? ax.taskData.taskName : 'N/A' }}
                        </div>
                      </el-dropdown-item>
                    </template>
                  </el-dropdown-menu>
                </template>
              </el-dropdown>
            </div>
            <div class="column columnHover">
              <el-button @click="deactivateAxis(axis)" class="trashButton">
                <font-awesome-icon :icon="['fas', 'trash']" />
              </el-button>
            </div>
          </div>
          <p class="heading twoLineText">{{ axis.taskData.taskName }}</p>
          <p class="subAxisName twoLineText">
            {{ $t(getTranslationPath(axis) + axis.categoryActive) }}
          </p>
          <p class="subAxisUnit twoLineText">
            {{
              $t(
                getTranslationPath(axis) + 'units.' + axis.categoryActive
              ).slice(-axis.categoryActive.length) !== axis.categoryActive
                ? $t(getTranslationPath(axis) + 'units.' + axis.categoryActive)
                : ''
            }}
          </p>
        </div>
      </div>
      <div
        class="axisControlsContainer axisPlusControlsContainer"
        :style="{ paddingLeft: `${axesSpacing / 2}px` }"
      >
        <div
          class="axisPlusContainer"
          v-for="index in activeAxes.length - 1"
          :key="index - 1 + 'plus'"
          :style="{
            width: `${axesSpacing / 3}px`,
            margin: `0 ${axesSpacing / 3}px`,
          }"
        >
          <el-dropdown
            class="axisPlus"
            v-if="
              availableAxes.length > 1 &&
              availableAxes.filter(
                (avAxis) => !this.axes.includes(avAxis) || !avAxis.active
              ).length >= 1
            "
            v-on:command="activateAxis($event, index)"
            trigger="click"
            placement="bottom"
          >
            <div class="el-dropdown-link">
              <font-awesome-icon :icon="['fas', 'circle-plus']" />
            </div>
            <template #dropdown>
              <el-dropdown-menu class="centered-dropdown-menu">
                <template
                  v-for="(ax, axIndex) in availableAxesForSelection.filter(
                    (avAxis) => !this.axes.includes(avAxis) || !avAxis.active
                  )"
                  :key="ax ? ax.taskData.taskId + 'ax' : axIndex + 'ax'"
                >
                  <el-dropdown-item
                    v-if="
                      (availableAxesForSelection[axIndex - 1] &&
                        ax.taskData.topicOrder !==
                          availableAxesForSelection[axIndex - 1].taskData
                            .topicOrder) ||
                      axIndex === 0
                    "
                    class="heading oneLineText"
                    :divided="true"
                    :style="{
                      pointerEvents: 'none',
                      paddingBottom: '0.02rem',
                      paddingTop: '0.02rem',
                    }"
                    disabled
                  >
                    {{ ax.taskData.topicName }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    :command="ax ? ax : null"
                    :divided="
                      (availableAxesForSelection[axIndex - 1] &&
                        ax.taskData.topicOrder !==
                          availableAxesForSelection[axIndex - 1].taskData
                            .topicOrder) ||
                      axIndex === 0
                    "
                  >
                    <div class="centered-dropdown-item">
                      <font-awesome-icon
                        class="axisIcon"
                        :icon="getIconOfAxis(ax)"
                        :style="{
                          color: getColorOfAxis(ax),
                        }"
                      />
                      &nbsp;{{ ax ? ax.taskData.taskName : 'N/A' }}
                    </div>
                  </el-dropdown-item>
                </template>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>
    </div>
    <div class="legend">
      <ToolTip
        v-for="legendItem in legend"
        class="legendItem"
        :key="legendItem.path"
        :content="$t(`moderator.organism.analytics.legend.${legendItem.name}`)"
        :show-after="200"
      >
        <el-image
          :src="legendItem.path"
          :alt="$t(`moderator.organism.analytics.legend.${legendItem.name}`)"
          :fit="'contain'"
        />
      </ToolTip>
    </div>
  </div>
</template>

<script lang="ts">
import { Avatar, ParticipantInfo } from '@/types/api/Participant';
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import TaskType from '@/types/enum/TaskType';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import {
  findIconDefinition,
  IconLookup,
  IconName,
  IconPrefix,
} from '@fortawesome/fontawesome-svg-core';
import { Idea } from '@/types/api/Idea';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import { debounce } from 'lodash';
import { reactive } from 'vue';
import VariableSVGWrapper from '@/components/moderator/organisms/analytics/subOrganisms/VariableSVGWrapper.vue';
import legend from '@/components/moderator/organisms/analytics/data/legend.json';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';

interface SubAxis {
  id: string;
  range: number;
}

interface Axis {
  taskData: {
    taskId: string;
    taskType: TaskType;
    taskName: string;
    topicName: string;
    topicOrder: number;
    moduleName: string;
    initOrder: number;
  };
  axisValues: (SubAxis | null)[];
  categoryActive: string;
  active: boolean;
  available: boolean;
}

interface AxisValue {
  id: string;
  value: number | null;
  ideas?: Idea[];
}

interface DataEntry {
  participant: ParticipantInfo;
  axes: {
    taskId: string;
    axisValues: AxisValue[];
  }[];
}

interface PathPart {
  path: string;
  dashed: boolean;
  x: number;
  y: number;
  value: number;
}

@Options({
  components: {
    ToolTip,
    VariableSVGWrapper,
    IdeaCard,
  },
  emits: ['update:selectedParticipantIds'],
})
export default class ParallelCoordinates extends Vue {
  @Prop({ default: () => [] }) chartAxes!: Axis[];
  @Prop({ default: () => [] }) participantData!: DataEntry[];
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];
  @Prop({ default: () => [] }) steps!: {
    taskData: {
      taskId: string;
      taskType: TaskType;
      taskName: string;
      topicName: string;
      topicOrder: number;
      moduleName: string;
      initOrder: number;
    };
    steps: TaskParticipantIterationStep[];
  }[];

  padding = 20;
  legend = legend;

  availableAxes: Axis[] = [];
  axes: Axis[] = [];

  averageAxisValues: number[] = [];
  chartData: DataEntry[] = [];

  parentWidth = 0;
  parentHeight = 0;
  resizeObserver: ResizeObserver | null = null;

  cachedIdeas: Record<string, { avatar: Avatar; ideas: Idea[] }[]> = reactive(
    {}
  );

  mounted() {
    this.calculateParentDimensions();
    this.resizeObserver = new ResizeObserver(
      debounce((entries) => {
        for (const entry of entries) {
          if (entry.target === this.$refs.parentElement) {
            this.calculateParentDimensions();
          }
        }
      }, 100)
    );

    const parentElement = this.$refs.parentElement as HTMLElement;
    if (parentElement) {
      this.resizeObserver.observe(parentElement);
    }
    this.cacheAllIdeas();
  }

  beforeDestroy() {
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
  }

  get availableAxesForSelection(): Axis[] {
    return this.availableAxes.filter(
      (avAxis) => !this.axes.includes(avAxis) || !avAxis.active
    );
  }

  participantSelectionChanged(id: string) {
    const newValue = this.selectedParticipantIds.includes(id)
      ? this.selectedParticipantIds.filter((i) => i !== id)
      : [id];
    this.$emit('update:selectedParticipantIds', newValue);
  }

  getTranslationPath(axis: Axis | null): string {
    if (!axis) {
      return '';
    }
    return `module.${axis.taskData.taskType.toLowerCase()}.${
      axis.taskData.moduleName
    }.analytics.highscore.`;
  }

  calculateParentDimensions() {
    const parentElement = this.$refs.svgElement as HTMLElement;
    if (parentElement) {
      this.parentWidth = parentElement.clientWidth;
      this.parentHeight = parentElement.clientHeight;
    }
  }

  get width() {
    return this.parentWidth || 800;
  }

  get height() {
    return this.parentHeight || 500;
  }

  get paddingLeft() {
    return this.axesSpacing / 4;
  }

  isIdeaTask(axis: Axis) {
    return (
      (axis.taskData.taskType as string) === 'BRAINSTORMING' ||
      (axis.taskData.taskType as string) === 'VOTING'
    );
  }

  @Watch('chartAxes', { immediate: true })
  onAxesChanged(): void {
    const updatedAxes = this.chartAxes
      .sort((a, b) => a.taskData.initOrder - b.taskData.initOrder)
      .reduce((acc, chartAxis) => {
        const matchingAxis = this.availableAxes.find(
          (axis) => axis.taskData.taskId === chartAxis.taskData.taskId
        );

        if (matchingAxis) {
          chartAxis.active = matchingAxis.active;
          chartAxis.available = matchingAxis.available;
        } else {
          acc.push(chartAxis);
        }

        return acc;
      }, [] as typeof this.chartAxes);

    this.availableAxes = [...this.availableAxes, ...updatedAxes];
    this.axes = this.availableAxes.filter(
      (axis) => axis.active && axis.available
    );
  }

  @Watch('participantData', { immediate: true })
  onChartDataChanged(): void {
    this.chartData = this.participantData;
  }

  getIconDefinition = (iconName: string, prefix = 'fas') => {
    const lookup: IconLookup = {
      prefix: prefix as IconPrefix,
      iconName: iconName as IconName,
    };
    return findIconDefinition(lookup);
  };

  getIconOfAxis(axis: Axis): string | undefined {
    if (axis.taskData.taskType) {
      return getIconOfType(TaskType[axis.taskData.taskType.toUpperCase()]);
    }
  }

  getColorOfAxis(axis: Axis): string | undefined {
    if (axis.taskData.taskType) {
      return getColorOfType(TaskType[axis.taskData.taskType.toUpperCase()]);
    }
  }

  get activeAxes() {
    return this.axes
      .filter((axis) => axis.active)
      .map((axis) => {
        return {
          ...axis,
          axisValues: axis.axisValues.filter((subAxis) => subAxis !== null),
        };
      });
  }

  get axesSpacing(): number {
    return this.width / this.activeAxes.length;
  }

  getParticipationCount(axis: Axis): number {
    const taskId = axis.taskData.taskId;
    const category = axis.categoryActive;

    return this.participantData.reduce((counter, partData) => {
      const partAxis = partData.axes.find(partAxis => partAxis.taskId === taskId);
      if (partAxis) {
        counter += partAxis.axisValues.reduce((count, value) => {
          return count + (value.id === category && value.value !== null ? 1 : 0);
        }, 0);
      }
      return counter;
    }, 0);
  }

  getYPosition(value: number, axis: Axis) {
    const activeSubAxis = axis.axisValues.find(
      (subAxis) => subAxis && subAxis.id === axis.categoryActive
    );

    const maxValue = activeSubAxis ? activeSubAxis.range : 3;
    return (
      this.height -
      this.padding -
      (value / maxValue) * (this.height - 2 * this.padding)
    );
  }

  getLabelPosition(index: number, labelCount: number) {
    return (
      this.height -
      this.padding -
      ((this.height - 2 * this.padding) / labelCount) * index
    );
  }

  getLabelCount(range?: number): number {
    if (!range) {
      return 3;
    }
    for (let i = 3; i <= 5; i++) {
      if (range % i === 0) {
        return i;
      }
    }
    return 3;
  }

  updateSubAxis(index: number, subAxisId: string | null) {
    const axis = this.axes.find(
      (a) => a.taskData.taskId === this.activeAxes[index].taskData.taskId
    );
    if (subAxisId && axis) {
      axis.categoryActive = subAxisId;
    }
  }

  getValuesForEntry(entry: DataEntry): (number | null)[] {
    return this.activeAxes.map((activeAxis) => {
      const matchingAxis = entry.axes.find(
        (axis) => axis.taskId === activeAxis.taskData.taskId
      );
      const value = matchingAxis
        ? matchingAxis.axisValues.find(
            (value) => value.id === activeAxis.categoryActive
          )?.value
        : null;
      return value == null ? null : value;
    });
  }

  getDataLine(entry: DataEntry): (PathPart | undefined)[] {
    const values = this.getValuesForEntry(entry);

    return values
      .map((value, index) => {
        if (value == null) return;

        const axis = this.activeAxes[index];
        const x = this.axesSpacing * index + this.paddingLeft;
        const y = this.getYPosition(value, axis);
        const isDashed = this.checkIfDashed(values, index);

        if (index === 0) {
          const { controlX1, controlX2 } = this.calculateControlPoints(x, x);
          return this.createPathPart(
            x,
            y,
            x,
            y,
            false,
            value,
            controlX1,
            controlX2
          );
        }

        const { prevX, prevY } = this.getPreviousCoordinates(values, index, y);
        const { controlX1, controlX2 } = this.calculateControlPoints(prevX, x);

        return this.createPathPart(
          prevX,
          prevY,
          x,
          y,
          isDashed,
          value,
          controlX1,
          controlX2
        );
      })
      .filter((part) => part);
  }

  get averageDataLinePath() {
    const averages: number[] = this.activeAxes
      .map((axis) => {
        const validValues = this.chartData
          .map((entry) => {
            const selectedAxis = entry.axes.find(
              (a) => a.taskId === axis.taskData.taskId
            );
            return selectedAxis
              ? selectedAxis.axisValues.find(
                  (value) => value.id === axis.categoryActive
                )?.value
              : null;
          })
          .filter((value): value is number => value !== null);

        return validValues.length > 0
          ? validValues.reduce((a, b) => a + b) / validValues.length
          : null;
      })
      .filter((avg) => avg !== null) as number[];

    this.averageAxisValues = averages;
    return averages
      .map((average, index) => {
        const x = this.axesSpacing * index + this.paddingLeft;
        const y =
          average != null
            ? this.getYPosition(average, this.activeAxes[index])
            : this.height / 2;
        const isDashed = this.checkIfDashed(averages, index);

        if (index === 0) {
          return this.createPathPart(x, y, x, y, false, average);
        }

        const { prevX, prevY } = this.getPreviousCoordinates(
          averages,
          index,
          y
        );
        const { controlX1, controlX2 } = this.calculateControlPoints(prevX, x);

        return this.createPathPart(
          prevX,
          prevY,
          x,
          y,
          isDashed,
          average,
          controlX1,
          controlX2
        );
      })
      .filter((part) => part);
  }

  getAboveBelow(
    threshold: number | undefined,
    index: number,
    below = false
  ): number {
    if (threshold == null) return 0;

    return this.chartData.reduce((count, entry) => {
      const value = this.getValuesForEntry(entry)[index];
      return value != null &&
        ((below && value < threshold) || (!below && value > threshold))
        ? count + 1
        : count;
    }, 0);
  }

  private checkIfDashed(values: (number | null)[], index: number): boolean {
    let isDashed = false;
    let iterator = index - 1;
    while (values[iterator] === null && iterator > 0) {
      iterator -= 1;
      isDashed = true;
    }
    return isDashed;
  }

  private getPreviousCoordinates(
    values: (number | null)[],
    index: number,
    currentY: number
  ): { prevX: number; prevY: number } {
    let iterator = index - 1;
    while (values[iterator] === null && iterator > 0) {
      iterator -= 1;
    }
    const prevX =
      values[iterator] !== null
        ? this.axesSpacing * iterator + this.paddingLeft
        : this.axesSpacing * index + this.paddingLeft;
    const prevY =
      values[iterator] !== null
        ? this.getYPosition(values[iterator] || 0, this.activeAxes[iterator])
        : currentY;
    return { prevX, prevY };
  }

  private calculateControlPoints(
    prevX: number,
    currentX: number
  ): { controlX1: number; controlX2: number } {
    let controlX1 = prevX + this.axesSpacing / 2;
    let controlX2 = currentX - this.axesSpacing / 2;

    if (currentX === prevX) {
      controlX1 = prevX + this.axesSpacing / 4;
      controlX2 = currentX - this.axesSpacing / 4;
    }

    return { controlX1, controlX2 };
  }

  private createPathPart(
    prevX: number,
    prevY: number,
    currentX: number,
    currentY: number,
    dashed: boolean,
    value: number,
    controlX1?: number,
    controlX2?: number
  ): PathPart {
    const path =
      controlX1 && controlX2
        ? `M${prevX},${prevY} C${controlX1},${prevY} ${controlX2},${currentY} ${currentX},${currentY}`
        : `M${currentX},${currentY}`;
    return { path, dashed, x: currentX, y: currentY, value };
  }

  draggedIndex: number | null = null;
  onDragStart(index: number) {
    this.draggedIndex = index;
  }

  onDrop(dropIndex: number): void {
    if (this.draggedIndex === null || this.draggedIndex === dropIndex) return;

    const draggedAxis = this.axes[this.draggedIndex];
    const dropAxis = this.axes[dropIndex];

    [this.axes[this.draggedIndex], this.axes[dropIndex]] = [
      dropAxis,
      draggedAxis,
    ];

    const draggedAvailableIndex = this.availableAxes.findIndex(
      (axis) => axis.taskData.taskId === draggedAxis.taskData.taskId
    );
    const dropAvailableIndex = this.availableAxes.findIndex(
      (axis) => axis.taskData.taskId === dropAxis.taskData.taskId
    );
    [
      this.availableAxes[draggedAvailableIndex],
      this.availableAxes[dropAvailableIndex],
    ] = [
      this.availableAxes[dropAvailableIndex],
      this.availableAxes[draggedAvailableIndex],
    ];

    this.chartData.forEach((entry) => {
      const [draggedDataEntry] = entry.axes.splice(this.draggedIndex || 0, 1);
      entry.axes.splice(dropIndex, 0, draggedDataEntry);
    });

    this.draggedIndex = null;
  }

  deactivateAxis(axis: Axis) {
    const axisIndex = this.axes.findIndex((a) => a.taskData.taskId === axis.taskData.taskId);
    if (axisIndex !== -1) {
      this.axes[axisIndex].active = false;
      this.axes.splice(axisIndex, 1);
    }
  }

  activateAxis(axis: Axis, index: number, replace = false) {
    this.axes.splice(index, replace ? 1 : 0, axis);
    this.axes[index].active = true;
  }

  private cacheAllIdeas() {
    for (const axis of this.activeAxes) {
      this.cachedIdeas[axis.taskData.taskId] = this.computeIdeasForAxis(axis);
    }
  }

  getCachedIdeas(axis: Axis): { avatar: Avatar; ideas: Idea[] }[] {
    return this.cachedIdeas[axis.taskData.taskId] || [];
  }

  @Watch('steps', { immediate: true, deep: true })
  onStepsChanged() {
    this.cacheAllIdeas();
  }

  computeIdeasForAxis(axis: Axis): { avatar: Avatar; ideas: Idea[] }[] {
    const steps = this.steps.find((step) => step.taskData.taskId === axis.taskData.taskId);
    if (!steps) return [];

    const { taskType } = steps.taskData;
    return (taskType as string) === 'VOTING'
      ? this.getVoteIdeasForList(steps.steps)
      : (taskType as string) === 'BRAINSTORMING'
      ? this.getBrainstormingIdeasForList(steps.steps)
      : [];
  }

  getBrainstormingIdeasForList(
    steps: TaskParticipantIterationStep[]
  ): { avatar: Avatar; ideas: Idea[] }[] {
    return steps.map((step) => ({
      avatar: step.avatar,
      ideas: step.parameter.ideas,
    }));
  }

  getVoteIdeasForList(
    steps: TaskParticipantIterationStep[]
  ): { avatar: Avatar; ideas: Idea[] }[] {
    return steps.map((step) => {
      return {
        avatar: step.avatar,
        ideas: step.parameter.ideas.map((i) => i.idea),
      };
    });
  }

  setHoverStroke(color: string, id: string, participantColor: string) {
    if (this.selectedParticipantIds.includes(id)) {
      return;
    }
    const elements = Array.from(
      document.getElementsByClassName(id) as HTMLCollectionOf<HTMLElement>
    );

    elements.forEach((element) => {
      const children = Array.from(element.children) as HTMLElement[];
      const circle = children.find((el) => el.classList.contains('circle'));
      const pathSegment = children.find((el) =>
        el.classList.contains('dataLinePathSegment')
      );
      const pathIcon = children.find((el) =>
        el.classList.contains('participantDataLineIcon')
      );

      const isParticipantColor = participantColor === color;
      const strokeWidth = isParticipantColor ? '3px' : '1px';
      const circleRadius = isParticipantColor ? '3px' : '0px';
      const iconOpacity = isParticipantColor ? '1' : '0';
      const pathOpacity = isParticipantColor
        ? !pathSegment?.classList.contains('dashed')
          ? '1'
          : '45%'
        : !pathSegment?.classList.contains('dashed')
        ? '45%'
        : '20%';

      Object.assign(element.style, { stroke: color, strokeWidth });

      if (circle) {
        circle.setAttribute('r', circleRadius);
        circle.setAttribute('fill', color);
      }

      if (pathIcon) {
        pathIcon.style.opacity = iconOpacity;
      }

      if (pathSegment) {
        pathSegment.style.opacity = pathOpacity;
      }
    });
  }
}
</script>

<style lang="scss" scoped>
.parallelCoordinatesContainer {
  position: relative;
  width: 100%;
  height: 100%;
  display: block;
  .parallelSVG {
    width: 100%;
    height: 77%;
  }
  .controls {
    position: relative;
    width: 100%;
    height: 20%;
  }
  .axisControlsContainer {
    height: 100%;
    width: 100%;
    display: flex;
  }
  .axisPlusControlsContainer {
    position: absolute;
    top: 0;
    pointer-events: none;
    .axisPlusContainer {
      z-index: 100;
      pointer-events: all;
    }
  }
  .participationContainer {
    height: 3%;
    width: 100%;
    display: flex;
  }
}

.dataLineHover {
  pointer-events: stroke;
  stroke-width: 15px;
  stroke: transparent;
  opacity: 40%;
}

.axes {
  transition: stroke 0.3s ease, stroke-width 0.3s ease;
  stroke: var(--color-dark-contrast);
}

.dataLine {
  pointer-events: none;
  transition: stroke 0.2s ease, stroke-width 0.2s ease, opacity 0.2s ease,
    transform 1s ease;
}

.circle {
  transition: 0.2s ease;
}

.dataLineHover:hover + .dataLine {
  opacity: 100%;
  stroke: var(--color-dark-contrast) !important;
}

.averageDataLine {
  pointer-events: none;
  stroke-width: 5px;
  stroke: var(--color-evaluating);
  opacity: 100%;
}

.axisControlsContainer {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: flex-start;
}
.axisControls {
  display: flex;
  flex-direction: column;
  align-items: center;
  .axisSelections {
    display: flex;
    align-items: center;
    justify-content: center;
    width: fit-content;
    gap: 1rem;
  }
  .axisIcon {
    font-size: var(--font-size-xlarge);
    cursor: pointer;
  }
  .heading {
    margin: 0;
  }
  .subAxisName {
    font-size: var(--font-size-small);
  }
  .subAxisUnit {
    font-size: var(--font-size-xsmall);
    font-weight: var(--font-weight-bold);
  }
}

.axisControlsContainer {
  .axisControls {
    cursor: grab;
  }
  .axisControls:active {
    cursor: grabbing;
  }
}

.column {
  flex: 1;
  display: flex;
  justify-content: center;
  align-items: center;
  width: 30%;
  padding: 0;
}

.columnHover {
  opacity: 0;
  transition: opacity 0.3s ease;
}

.columnCenter {
  width: 40% !important;
}

.axisControls:hover {
  .columnHover {
    opacity: 1;
  }
}

.axisControlsFlex {
  display: flex;
  flex-direction: row;
  justify-content: flex-start;
  align-items: center;
}

.axisPlusContainer {
  opacity: 0;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: opacity 0.5s ease;
  height: calc(var(--font-size-xxxlarge) * 2);
}

.axisPlusContainer:hover {
  opacity: 1;
}

.axisPlus {
  font-size: var(--font-size-xxxlarge);
  opacity: 0.5;
  transition: opacity 0.5s ease;
  cursor: pointer;
}

.axisPlus:hover {
  opacity: 1;
}

.trashButton,
.cogButton {
  background-color: transparent;
  padding: 0;
  margin: 0;
  font-size: var(--font-size-small);
  cursor: pointer;
}

.listIcon {
  cursor: pointer;
}

.participantDataLineIcon {
  transition: opacity 0.3s ease;
}

.aboveBelow {
  font-weight: var(--font-weight-bold);
}

.dropDownIdeaContainer {
  display: flex;
  flex-direction: column;
  min-width: 10rem;
  max-width: 30rem;
  .IdeaCard {
    width: 100%;
  }
}

.legend {
  display: flex;
  flex-direction: row;
  gap: 1rem;
  height: 1.9rem;
  width: fit-content;
  padding: 0.4rem 0.7rem;
  border: 2px solid var(--color-background-dark);
  border-radius: var(--border-radius-xs);
  background-color: var(--color-background);
  z-index: 100;
  margin-left: auto;
  margin-right: 0;
  margin-top: 1rem;
  cursor: help;
}
</style>
