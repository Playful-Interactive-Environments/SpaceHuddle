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
        :key="axis.moduleId"
        :style="{
          textAlign: 'center',
          width: `${axesSpacing}px`,
          gap: `${axesSpacing / 4}px`,
          paddingLeft: `${axesSpacing / 3}px`,
        }"
      >
        <p class="listButton">
          <el-dropdown
            trigger="click"
            placement="bottom"
            v-if="isIdeaTask(axis)"
            :max-height="height"
            :hide-on-click="false"
          >
            <div class="el-dropdown-link">
              <font-awesome-icon icon="list" />
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item
                  v-for="entry of getCachedIdeas(axis)"
                  :key="entry.avatar.id + axis.moduleId"
                  :divided="true"
                  :disabled="true"
                  :style="{
                    cursor: 'default',
                    flexDirection: 'column',
                    alignItems: 'flex-start',
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
                        entry.avatar.id +
                        axis.moduleId +
                        (idea ? idea.id : index)
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
        </p>
        <p class="participantCount">
          <font-awesome-icon icon="user" /> {{ getParticipationCount(axis) }}
        </p>
      </div>
    </div>

    <svg
      ref="svgElement"
      class="parallelSVG"
      :style="{ paddingLeft: `${axesSpacing / 2}px` }"
    >
      <!-- Axes -->
      <g
        class="axes"
        v-for="(axis, index) in activeAxes"
        :key="axis.moduleId"
        :transform="`translate(${axesSpacing * index}, 0)`"
      >
        <!-- Axis Line -->
        <line :y1="padding" :y2="height - padding" stroke="black" />

        <!-- Tick Marks and Labels for the active sub-axis -->
        <g
          v-for="labelIndex in labelCount + 1"
          :key="labelIndex - 1 + axis.moduleId"
        >
          <VariableSVGWrapper>
            <template
              v-slot="{ labelPosition = getLabelPosition(labelIndex - 1) }"
            >
              <line
                :y1="labelPosition"
                :y2="labelPosition"
                :x2="5"
                stroke="black"
              />
              <text :y="labelPosition" x="10" font-size="10">
                {{
                  Math.round(
                    ((axis.axisValues.find(
                      (value) => value.id === axis.categoryActive
                    ).range /
                      labelCount) *
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
          <template v-slot="{ dataLine = getDataLine(entry) }">
            <text>{{ dataLine.length }}</text>
            <g
              v-for="pathPart in dataLine"
              :key="pathPart?.path"
              class="dataLineHover"
              fill="none"
              @mouseenter="hoverStroke = entry.participant.avatar.color"
              @mouseleave="hoverStroke = null"
            >
              <path v-if="pathPart" :d="pathPart.path" />
            </g>
            <g
              v-for="(pathPart, index) in dataLine"
              :key="pathPart?.path"
              class="dataLine"
              fill="none"
              :stroke="
                hoverStroke === entry.participant.avatar.color
                  ? hoverStroke
                  : 'var(--color-dark-contrast)'
              "
              :style="{
                strokeWidth:
                  hoverStroke === entry.participant.avatar.color
                    ? '3px'
                    : '1px',
              }"
            >
              <path
                class="participantDataLineIcon"
                v-if="index === dataLine.length - 1"
                :transform="`translate(${pathPart?.x + 20}, ${
                  pathPart?.y - 10
                }), scale(0.04)`"
                :d="getIconDefinition(entry.participant.avatar.symbol).icon[4] as string"
                :fill="entry.participant.avatar.color"
                :style="{
                  opacity:
                    hoverStroke === entry.participant.avatar.color ? '1' : '0',
                }"
              />
              <circle
                class="circle"
                :r="
                  hoverStroke === entry.participant.avatar.color ||
                  dataLine.length <= 1
                    ? '3px'
                    : '0px'
                "
                :cx="pathPart?.x"
                :cy="pathPart?.y"
                :fill="
                  hoverStroke === entry.participant.avatar.color ||
                  dataLine.length > 1
                    ? hoverStroke
                    : 'var(--color-dark-contrast)'
                "
                :style="{
                  opacity:
                    hoverStroke === entry.participant.avatar.color ||
                    dataLine.length <= 1
                      ? '1'
                      : '0',
                }"
              />
              <path
                v-if="pathPart"
                class="dataLinePathSegment"
                :d="pathPart.path"
                :stroke-dasharray="pathPart.dashed ? '4,4' : '0'"
                :style="{
                  opacity: pathPart?.dashed
                    ? '20%'
                    : hoverStroke === entry.participant.avatar.color
                    ? '1'
                    : '45%',
                }"
              />
            </g>
          </template>
        </VariableSVGWrapper>
      </g>
    </svg>

    <!-- Dropdown Menus -->
    <div class="controls">
      <div class="axisControlsContainer">
        <div
          class="axisControls"
          v-for="(axis, index) in activeAxes"
          :key="axis.moduleId"
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
            <el-dropdown
              v-if="axis.axisValues.length > 1"
              v-on:command="updateSubAxis(index, $event)"
              trigger="click"
              placement="bottom"
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
                    {{ subAxis ? subAxis.id : 'N/A' }}
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
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
                <el-dropdown-menu>
                  <el-dropdown-item
                    v-for="(ax, axIndex) in availableAxes.filter(
                      (avAxis) => !this.axes.includes(avAxis) || !avAxis.active
                    )"
                    :key="ax ? ax.moduleId + 'ax' : axIndex + 'ax'"
                    :command="ax ? ax : null"
                  >
                    {{ ax ? ax.taskData.taskName : 'N/A' }}
                  </el-dropdown-item>
                </el-dropdown-menu>
              </template>
            </el-dropdown>
            <el-button @click="deactivateAxis(axis)" class="trashButton">
              <font-awesome-icon :icon="['fas', 'trash']" />
            </el-button>
          </div>
          <p class="axisName twoLineText">{{ axis.taskData.taskName }}</p>
          <p class="subAxisName twoLineText">{{ axis.categoryActive }}</p>
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
              <el-dropdown-menu>
                <el-dropdown-item
                  v-for="(ax, axIndex) in availableAxes.filter(
                    (avAxis) => !this.axes.includes(avAxis) || !avAxis.active
                  )"
                  :key="ax ? ax.moduleId + 'ax' : axIndex + 'ax'"
                  :command="ax ? ax : null"
                >
                  {{ ax ? ax.taskData.taskName : 'N/A' }}
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </div>
      </div>
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
import VariableSVGWrapper from '@/modules/common/visualisation_master/organisms/subOrganisms/VariableSVGWrapper.vue';

interface SubAxis {
  id: string;
  range: number;
}

interface Axis {
  moduleId: string;
  taskData: {
    taskType: TaskType;
    taskName: string;
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
}

interface DataEntry {
  participant: ParticipantInfo;
  axes: {
    moduleId: string;
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
    VariableSVGWrapper,
    IdeaCard,
  },
})
export default class ParallelCoordinates extends Vue {
  @Prop({ default: () => [] }) chartAxes!: Axis[];
  @Prop({ default: () => [] }) participantData!: DataEntry[];
  @Prop({ default: () => [] }) steps!: {
    moduleId: string;
    taskData: {
      taskType: TaskType;
      taskName: string;
    };
    steps: TaskParticipantIterationStep[];
  }[];

  padding = 20;
  hoverStroke: string | null = null;

  availableAxes: Axis[] = [];
  axes: Axis[] = [];

  averageAxisValues: number[] = [];

  chartData: DataEntry[] = [];

  labelCount = 3;

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
          (axis) => axis.moduleId === chartAxis.moduleId
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
    const category = axis.categoryActive;
    return this.participantData.reduce((counter, partData) => {
      const partAxis = partData.axes.find(
        (partAxis) => partAxis.moduleId === axis.moduleId
      );
      if (partAxis) {
        counter += partAxis.axisValues.filter(
          (value) => value.id === category && value.value !== null
        ).length;
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

  getLabelPosition(index: number) {
    return (
      this.height -
      this.padding -
      ((this.height - 2 * this.padding) / this.labelCount) * index
    );
  }

  updateSubAxis(index: number, subAxisId: string | null) {
    const axis = this.axes.find(
      (a) => a.moduleId === this.activeAxes[index].moduleId
    );
    if (subAxisId && axis) {
      axis.categoryActive = subAxisId;
    }
  }

  getValuesForEntry(entry: DataEntry): (number | null)[] {
    return this.activeAxes.map((activeAxis) => {
      const matchingAxis = entry.axes.find(
        (axis) => axis.moduleId === activeAxis.moduleId
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
    console.log("redraw");
    const values = this.getValuesForEntry(entry);

    return values
      .map((value, index) => {
        if (value == null) return;

        const axis = this.activeAxes[index];
        const x = this.axesSpacing * index;
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
              (a) => a.moduleId === axis.moduleId
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
        const x = this.axesSpacing * index;
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
        ? this.axesSpacing * iterator
        : this.axesSpacing * index;
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
      (axis) => axis.moduleId === draggedAxis.moduleId
    );
    const dropAvailableIndex = this.availableAxes.findIndex(
      (axis) => axis.moduleId === dropAxis.moduleId
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
    const axisIndex = this.axes.findIndex((a) => a.moduleId === axis.moduleId);
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
      this.cachedIdeas[axis.moduleId] = this.computeIdeasForAxis(axis);
    }
  }

  getCachedIdeas(axis: Axis): { avatar: Avatar; ideas: Idea[] }[] {
    return this.cachedIdeas[axis.moduleId] || [];
  }

  @Watch('steps', { immediate: true, deep: true })
  onStepsChanged() {
    this.cacheAllIdeas();
  }

  computeIdeasForAxis(axis: Axis): { avatar: Avatar; ideas: Idea[] }[] {
    const steps = this.steps.find((step) => step.moduleId === axis.moduleId);
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
    return steps.map((step) => ({
      avatar: step.avatar,
      ideas: step.parameter.ideas.map((i) => i.idea),
    }));
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
  transition: stroke 0.3s ease, stroke-width 0.3s ease, transform 0.6s ease;
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
  .axisSelections {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
  }
  .axisIcon {
    font-size: var(--font-size-xlarge);
  }
  .axisName {
    font-size: var(--font-size-default);
    font-weight: var(--font-weight-bold);
  }
  .subAxisName {
    font-size: var(--font-size-small);
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
</style>
