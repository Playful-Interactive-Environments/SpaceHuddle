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

      <!-- Data Lines as Curves -->
      <!--      <g v-for="entry in chartData" :key="entry.participant.id">
        <path
          class="dataLineHover"
          :d="getDataLine(entry)"
          fill="none"
          @mouseenter="hoverStroke = entry.participant.avatar.color"
          @mouseleave="hoverStroke = null"
        />
        <path
          class="dataLine"
          :d="getDataLine(entry)"
          fill="none"
          :stroke="
            hoverStroke === entry.participant.avatar.color
              ? hoverStroke
              : 'var(&#45;&#45;color-dark-contrast)'
          "
        />
      </g>-->
      <!-- Average Data Line -->
      <path
        class="dataLine averageDataLine"
        :d="getAverageDataLinePath()"
        fill="none"
      />

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
            :r="hoverStroke === entry.participant.avatar.color ? '3px' : '0px'"
            :cx="pathPart.x"
            :cy="pathPart.y"
            :fill="entry.participant.avatar.color"
            :style="{
              opacity:
                hoverStroke === entry.participant.avatar.color ? '1' : '0',
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
        left: `${axesSpacing * index - axesSpacing / 2}px`,
        top: '100%',
        textAlign: 'center',
        width: `${axesSpacing}px`,
      }"
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
        <font-awesome-icon
          class="axisIcon"
          :icon="getIconOfAxis(axis)"
          :style="{
            color: getColorOfAxis(axis),
          }"
        />
      </div>
      <p class="axisName">{{ axis.taskData.taskName }}</p>
      <p class="subAxisName">{{ axis.categoryActive }}</p>
      <!--      <select @change="updateSubAxis(index, $event.target.value)">
        <option
          v-for="(subAxis, subAxisIndex) in axis.axisValues"
          :key="subAxis ? subAxis.id : subAxisIndex"
          :value="subAxis ? subAxis.id : null"
        >
          {{ subAxis ? subAxis.id : 'N/A' }}
        </option>
      </select>-->
    </div>
  </div>
</template>

<script lang="ts">
import { ParticipantInfo } from '@/types/api/Participant';
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import TaskType from '@/types/enum/TaskType';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import { library, dom } from '@fortawesome/fontawesome-svg-core';

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

export default class Analytics extends Vue {
  @Prop({ default: () => [] }) chartAxes!: Axis[];
  @Prop({ default: () => [] }) participantData!: DataEntry[];

  height = 500;
  padding = 20;

  hoverStroke: string | null = null;

  axes: Axis[] = [];
  chartData: DataEntry[] = [];

  labelCount = 3;

  @Watch('chartAxes', { immediate: true })
  onAxesChanged(): void {
    this.axes = this.chartAxes;
  }

  @Watch('participantData', { immediate: true })
  onChartDataChanged(): void {
    this.chartData = this.participantData;
  }

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
    return window.innerWidth - 300;
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

  /*getDataLine(entry: DataEntry) {
    const values = entry.axes
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

    const pathParts = values.map((value, index) => {
      const axis = this.activeAxes[index];
      const x = this.axesSpacing * index;
      const y =
        value !== null ? this.getYPosition(value!, axis) : this.height / 2;

      if (index === 0) {
        return `M${x},${y}`;
      }

      const prevX = this.axesSpacing * (index - 1);
      const prevY =
        values[index - 1] !== null
          ? this.getYPosition(values[index - 1]!, this.activeAxes[index - 1])
          : this.height / 2;

      const controlX1 = prevX + this.axesSpacing / 2;
      const controlX2 = x - this.axesSpacing / 2;

      return `C${controlX1},${prevY} ${controlX2},${y} ${x},${y}`;
    });

    return pathParts.join(' ');
  }*/

  getDataLine(
    entry: DataEntry
  ): ({ path: string; dashed: boolean; x: number; y: number } | undefined)[] {
    const values = entry.axes
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

    const pathParts = values.map((value, index) => {
      if (value != null) {
        let isDashed = false;
        const axis = this.activeAxes[index];
        const x = this.axesSpacing * index;
        const y = this.getYPosition(value!, axis);

        if (index === 0) {
          return { path: `M${x},${y}`, dashed: false, x: x, y: y };
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
    if (subAxisId) {
      this.axes[index].categoryActive = subAxisId;
      console.log(this.axes[index]);
    }
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

    return averages;
  }

  getAverageDataLinePath() {
    const averages = this.getAverageDataLine();

    const pathParts = averages.map((average, index) => {
      const x = this.axesSpacing * index;
      const y =
        average !== null
          ? this.getYPosition(average, this.activeAxes[index])
          : this.height / 2;

      if (index === 0) {
        return `M${x},${y}`;
      }

      const prevX = this.axesSpacing * (index - 1);
      const prevY =
        averages[index - 1] !== null
          ? this.getYPosition(averages[index - 1], this.activeAxes[index - 1])
          : this.height / 2;

      const controlX1 = prevX + this.axesSpacing / 2;
      const controlX2 = x - this.axesSpacing / 2;

      return `C${controlX1},${prevY} ${controlX2},${y} ${x},${y}`;
    });

    return pathParts.join(' ');
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
</style>
