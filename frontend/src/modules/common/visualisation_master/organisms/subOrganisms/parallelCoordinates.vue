<template>
  <div style="position: relative">
    <svg :width="width" :height="height">
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
          <line
            :y1="getLabelPosition(labelIndex - 1)"
            :y2="getLabelPosition(labelIndex - 1)"
            :x2="5"
            stroke="black"
          />
          <text :y="getLabelPosition(labelIndex - 1)" x="10" font-size="10">
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
        </g>
      </g>

      <!-- Average Data Line -->
      <g
        v-for="(pathPart, index) in getAverageDataLinePath()"
        :key="pathPart.path"
        :fill="'var(--color-evaluating)'"
      >
        <g
          v-if="getAboveBelow(pathPart.value, index, true) > 0"
          :transform="`translate(${pathPart.x + 7}, ${pathPart.y + 5})`"
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
          v-if="getAboveBelow(pathPart.value, index, true) > 0"
          :transform="`translate(${pathPart.x + 7}, ${pathPart.y - 20})`"
        >
          <path
            :transform="`scale(0.025)`"
            :d="getIconDefinition('angle-up').icon[4] as string"
          />
          <text font-size="12" :transform="`translate(10, 0)`">
            {{ getAboveBelow(pathPart.value, index) }}
          </text>
        </g>
        <path
          v-if="pathPart"
          class="dataLine averageDataLine"
          :d="pathPart.path"
          fill="none"
        />
      </g>

      <!-- Data Lines as Curves -->
      <g v-for="entry in chartData" :key="entry.participant.id">
        <g
          v-for="pathPart in getDataLine(entry)"
          :key="pathPart.path"
          class="dataLineHover"
          fill="none"
          @mouseenter="hoverStroke = entry.participant.avatar.color"
          @mouseleave="hoverStroke = null"
        >
          <path v-if="pathPart" :d="pathPart.path" />
        </g>
        <g
          v-for="pathPart in getDataLine(entry)"
          :key="pathPart.path"
          class="dataLine"
          fill="none"
          :stroke="
            hoverStroke === entry.participant.avatar.color
              ? hoverStroke
              : 'var(--color-dark-contrast)'
          "
          :style="{
            strokeWidth:
              hoverStroke === entry.participant.avatar.color ? '3px' : '1px',
          }"
        >
          <circle
            class="circle"
            :r="
              hoverStroke === entry.participant.avatar.color ||
              getDataLine(entry).length <= 1
                ? '3px'
                : '0px'
            "
            :cx="pathPart.x"
            :cy="pathPart.y"
            :fill="
              hoverStroke === entry.participant.avatar.color ||
              getDataLine(entry).length > 1
                ? hoverStroke
                : 'var(--color-dark-contrast)'
            "
            :style="{
              opacity:
                hoverStroke === entry.participant.avatar.color ||
                getDataLine(entry).length <= 1
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
              opacity: pathPart.dashed
                ? '35%'
                : hoverStroke === entry.participant.avatar.color
                ? '1'
                : '35%',
            }"
          />
        </g>
      </g>
    </svg>

    <div
      class="axisControls"
      v-for="(axis, index) in activeAxes"
      :key="axis.moduleId + 1"
      :style="{
        position: 'absolute',
        left: `${axesSpacing * index - axesSpacing / 2}px`,
        bottom: '100%',
        textAlign: 'right',
        width: `${axesSpacing}px`,
        paddingRight: `${axesSpacing / 5}px`,
      }"
    >
      <p class="participantCount">
        <font-awesome-icon icon="user" /> {{ getParticipationCount(axis) }}
      </p>
    </div>

    <!-- Dropdown Menus -->
    <div
      class="axisControls"
      v-for="(axis, index) in activeAxes"
      :key="axis.moduleId"
      :style="{
        position: 'absolute',
        left: `${axesSpacing * index - axesSpacing / 2 + axesSpacing / 6}px`,
        top: '100%',
        textAlign: 'center',
        width: `${axesSpacing / 1.5}px`,
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
          <div class="el-dropdown-link">
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
          v-on:command="updateAxis(index, $event)"
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
                  (avAxis) => !this.axes.includes(avAxis)
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
      <p class="axisName">{{ axis.taskData.taskName }}</p>
      <p class="subAxisName">{{ axis.categoryActive }}</p>
      <el-button @click="deactivateAxis(axis)" class="trashButton">
        <font-awesome-icon :icon="['fas', 'trash']" />
      </el-button>
    </div>

    <div
      class="axisPlusContainer"
      v-for="index in activeAxes.length - 1"
      :key="index - 1 + 'plus'"
      :style="{
        position: 'absolute',
        left: `${axesSpacing * (index - 1) + axesSpacing / 4}px`,
        top: '100%',
        width: `${axesSpacing / 2}px`,
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
</template>

<script lang="ts">
import { ParticipantInfo } from '@/types/api/Participant';
import { Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import TaskType from '@/types/enum/TaskType';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';

import {
  findIconDefinition,
  IconLookup,
  IconName,
  IconPrefix,
} from '@fortawesome/fontawesome-svg-core';

interface SubAxis {
  id: string;
  range: number;
}

interface Axis {
  moduleId: string;
  taskData: {
    taskType: TaskType;
    taskName: string;
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

export default class Analytics extends Vue {
  @Prop({ default: () => [] }) chartAxes!: Axis[];
  @Prop({ default: () => [] }) participantData!: DataEntry[];

  height = 500;
  padding = 20;

  hoverStroke: string | null = null;

  availableAxes: Axis[] = [];
  axes: Axis[] = [];

  averageAxisValues: number[] = [];

  chartData: DataEntry[] = [];

  labelCount = 3;

  @Watch('chartAxes', { immediate: true })
  onAxesChanged(): void {
    this.availableAxes = this.chartAxes;
    this.axes = [...this.availableAxes];
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

  get width() {
    return window.innerWidth - window.innerWidth / 10;
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

  getParticipationCount(axis: Axis) {
    const category = axis.categoryActive;
    let counter = 0;
    for (const partData of this.participantData) {
      const partAxis = partData.axes.find(
        (partAxis) => partAxis.moduleId === axis.moduleId
      );
      if (partAxis) {
        counter += partAxis.axisValues.filter(
          (value) => value.id === category && value.value !== null
        ).length;
      }
    }
    return counter;
  }

  getValuesForEntry(entry: DataEntry) {
    return entry.axes
      .filter((axis) => axis.axisValues.length > 0)
      .map((axis) => {
        const selectedAxis = this.activeAxes.find(
          (a) => a.moduleId === axis.moduleId
        );
        return selectedAxis
          ? axis.axisValues.find(
              (value) => value.id === selectedAxis.categoryActive
            )?.value
          : null;
      });
  }

  getDataLine(entry: DataEntry): (PathPart | undefined)[] {
    // Align values only with activeAxes
    const values = this.activeAxes.map((activeAxis) => {
      const matchingAxis = entry.axes.find(
        (axis) => axis.moduleId === activeAxis.moduleId
      );
      if (!matchingAxis) {
        return null; // If no matching axis, return null
      }
      return matchingAxis.axisValues.find(
        (value) => value.id === activeAxis.categoryActive
      )?.value;
    });

    const pathParts = values.map((value, index) => {
      if (value != null) {
        let isDashed = false;
        const axis = this.activeAxes[index];
        const x = this.axesSpacing * index;
        const y = this.getYPosition(value!, axis);

        if (index === 0) {
          return {
            path: `M${x},${y} C${x - this.axesSpacing / 4},${y} ${
              x + this.axesSpacing / 4
            },${y} ${x},${y}`,
            dashed: false,
            x: x,
            y: y,
            value: value,
          };
        }

        let iterator = index - 1;
        while (values[iterator] === null && iterator > 0) {
          iterator -= 1;
          isDashed = true;
        }

        const prevX =
          values[iterator] !== null ? this.axesSpacing * iterator : x;
        const prevY =
          values[iterator] !== null
            ? this.getYPosition(values[iterator]!, this.activeAxes[iterator])
            : y;

        let controlX1 = prevX + this.axesSpacing / 2;
        let controlX2 = x - this.axesSpacing / 2;

        if (x === prevX) {
          controlX1 = prevX + this.axesSpacing / 4;
          controlX2 = x - this.axesSpacing / 4;
        }

        return {
          path: `M${prevX},${prevY} C${controlX1},${prevY} ${controlX2},${y} ${x},${y}`,
          dashed: x !== prevX ? isDashed : false,
          x: x,
          y: y,
          value: value,
        };
      }
    });

    return pathParts.filter((parts) => parts);
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

  updateAxis(index: number, axis: Axis) {
    this.axes[index] = structuredClone(axis);
    this.axes[index].active = true;
  }

  getAverageDataLine() {
    const averages: number[] = [];

    // Loop through each active axis
    for (const axis of this.activeAxes) {
      const values = this.chartData.map((entry) => {
        const selectedAxis = entry.axes.find(
          (a) => a.moduleId === axis.moduleId
        );
        if (selectedAxis) {
          const axisValue = selectedAxis.axisValues.find(
            (value) => value.id === axis.categoryActive
          );
          return axisValue ? axisValue.value : null;
        }
        return null;
      });

      // Calculate average while ignoring null values
      const validValues = values.filter((value) => value !== null) as number[];
      const average =
        validValues.length > 0
          ? validValues.reduce((a, b) => a + b) / validValues.length
          : null;

      // Only push the average if it's not null
      if (average !== null) {
        averages.push(average);
      }
    }

    this.averageAxisValues = averages;
    return averages;
  }

  getAverageDataLinePath(): (PathPart | undefined)[] {
    let isDashed = false;
    const averages = this.getAverageDataLine();

    const pathParts = averages.map((average, index) => {
      const x = this.axesSpacing * index;
      const y =
        average !== null
          ? this.getYPosition(average, this.activeAxes[index])
          : this.height / 2;

      if (index === 0) {
        return {
          path: `M${x},${y}`,
          dashed: false,
          x: x,
          y: y,
          value: average,
        };
      }

      let iterator = index - 1;
      while (averages[iterator] === null && iterator > 0) {
        iterator -= 1;
        isDashed = true;
      }

      const prevX =
        averages[iterator] !== null ? this.axesSpacing * iterator : x;
      const prevY =
        averages[iterator] !== null
          ? this.getYPosition(averages[iterator]!, this.activeAxes[iterator])
          : y;

      let controlX1 = prevX + this.axesSpacing / 2;
      let controlX2 = x - this.axesSpacing / 2;

      if (x === prevX) {
        controlX1 = prevX + this.axesSpacing / 4;
        controlX2 = x - this.axesSpacing / 4;
      }

      return {
        path: `M${prevX},${prevY} C${controlX1},${prevY} ${controlX2},${y} ${x},${y}`,
        dashed: x !== prevX ? isDashed : false,
        x: x,
        y: y,
        value: average,
      };
    });

    return pathParts.filter((parts) => parts);
  }

  getAboveBelow(threshold: number, index: number, below = false) {
    let count = 0;
    for (const entry of this.chartData) {
      const value = this.getValuesForEntry(entry)[index];
      if (value !== undefined && value !== null) {
        if (!below && value > threshold) {
          count += 1;
        }
        if (below && value < threshold) {
          count += 1;
        }
      }
    }
    return count;
  }

  draggedIndex: number | null = null;

  // Called when an axis control is picked up
  onDragStart(index: number) {
    this.draggedIndex = index;
  }

  // Called when an axis control is dropped
  onDrop(dropIndex: number) {
    if (this.draggedIndex === null || this.draggedIndex === dropIndex) return;

    // Reorder the activeAxes array
    const draggedAxis = this.activeAxes.splice(this.draggedIndex, 1)[0];
    this.activeAxes.splice(dropIndex, 0, draggedAxis);

    // Update the original axes array accordingly
    const draggedOriginalAxis = this.axes.splice(this.draggedIndex, 1)[0];
    this.axes.splice(dropIndex, 0, draggedOriginalAxis);

    for (const entry of this.chartData) {
      const draggedDataEntry = entry.axes.splice(this.draggedIndex, 1)[0];
      entry.axes.splice(dropIndex, 0, draggedDataEntry);
    }

    // Reset dragged index
    this.draggedIndex = null;
  }

  deactivateAxis(axis: Axis) {
    const axisIndex = this.axes.findIndex((a) => a.moduleId === axis.moduleId);
    if (axisIndex !== -1) {
      this.axes[axisIndex].active = false;
    }
    console.log(this.axes);
  }

  activateAxis(axis: Axis, index: number) {
    console.log(index);

    const axisIndexToActivate = this.axes.findIndex(
      (a) => a.moduleId === axis.moduleId
    );

    if (this.axes[axisIndexToActivate]) {
      this.axes[axisIndexToActivate].active = true;
    }

    // Reorder the activeAxes array
    const toActivateAxis = this.activeAxes.splice(axisIndexToActivate, 1)[0];
    this.activeAxes.splice(index, 0, toActivateAxis);

    // Update the original axes array accordingly
    const toActivateOriginalAxis = this.axes.splice(axisIndexToActivate, 1)[0];
    this.axes.splice(index, 0, toActivateOriginalAxis);

    for (const entry of this.chartData) {
      const toActivateDataEntry = entry.axes.splice(axisIndexToActivate, 1)[0];
      entry.axes.splice(index, 0, toActivateDataEntry);
    }
  }
}
</script>

<style lang="scss" scoped>
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

.trashButton {
  background-color: transparent;
  padding: 0;
  margin: 0;
  font-size: var(--font-size-small);
}
</style>
