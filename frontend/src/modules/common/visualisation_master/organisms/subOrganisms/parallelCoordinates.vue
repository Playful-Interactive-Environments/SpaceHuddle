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
            :y1="getLabelPosition(labelIndex - 1, axis)"
            :y2="getLabelPosition(labelIndex - 1, axis)"
            :x2="5"
            stroke="black"
          />
          <text
            :y="getLabelPosition(labelIndex - 1, axis)"
            x="10"
            font-size="10"
          >
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
          v-for="pathPart in getDataLine1(entry)"
          :key="pathPart.path"
          class="dataLineHover"
          fill="none"
          @mouseenter="hoverStroke = entry.participant.avatar.color"
          @mouseleave="hoverStroke = null"
        >
          <path v-if="pathPart" :d="pathPart.path" />
        </g>
        <g
          v-for="pathPart in getDataLine1(entry)"
          :key="pathPart.path"
          class="dataLine"
          fill="none"
          :stroke="
            hoverStroke === entry.participant.avatar.color
              ? hoverStroke
              : 'var(--color-dark-contrast)'
          "
        >
          <path
            v-if="pathPart"
            class="dataLinePathSegment"
            :d="pathPart.path"
            :stroke-dasharray="pathPart.dashed ? '4,4' : '0'"
          />
        </g>
      </g>
    </svg>

    <!-- Dropdown Menus -->
    <div
      v-for="(axis, index) in activeAxes"
      :key="axis.moduleId"
      :style="{
        position: 'absolute',
        left: `${axesSpacing * index}px`,
        bottom: '2rem', // Adjust this to position the dropdown as needed
      }"
    >
      <select @change="updateSubAxis(index, $event.target.value)">
        <option
          v-for="(subAxis, subAxisIndex) in axis.axisValues"
          :key="subAxis ? subAxis.id : subAxisIndex"
          :value="subAxis ? subAxis.id : null"
        >
          {{ subAxis ? subAxis.id : 'N/A' }}
        </option>
      </select>
    </div>

    <el-button @click="this.axes[2].active = !this.axes[2].active">
      Switch Axis
    </el-button>
  </div>
</template>

<script lang="ts">
import { ParticipantInfo } from '@/types/api/Participant';
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

interface SubAxis {
  id: string;
  range: number;
}

interface Axis {
  moduleId: string;
  name: string;
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
  width = 1200;
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

  get activeAxes() {
    return this.axes.map((axis) => {
      return {
        ...axis,
        axisValues: axis.axisValues.filter((subAxis) => subAxis !== null),
      };
    });
  }

  get axesSpacing(): number {
    return this.width / this.activeAxes.length;
  }

  getDataLine(entry: DataEntry) {
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
  }

  getDataLine1(entry: DataEntry): ({ path: string; dashed: boolean } | undefined)[] {
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
        const y =
          value !== null ? this.getYPosition(value!, axis) : this.height / 2;

        if (index === 0) {
          return { path: `M${x},${y}`, dashed: false };
        }

        let iterator = index - 1;
        while (values[iterator] === null && iterator > 0) {
          iterator -= 1;
          isDashed = true;
        }

        const prevX = this.axesSpacing * iterator;
        const prevY =
          values[iterator] !== null
            ? this.getYPosition(values[iterator]!, this.activeAxes[iterator])
            : this.height / 2;

        const controlX1 = prevX + this.axesSpacing / 2;
        const controlX2 = x - this.axesSpacing / 2;

        return {
          path: `M${prevX},${prevY} C${controlX1},${prevY} ${controlX2},${y} ${x},${y}`,
          dashed: isDashed,
        };
      }
    });
    return pathParts.filter((parts) => parts);
  }

  getLabelPosition(index: number, axis: Axis) {
    return (
      ((this.height - 2 * this.padding) / this.labelCount) * index +
      this.padding
    );
  }

  getYPosition(value: number, axis: Axis) {
    return (
      this.padding +
      (value /
        Math.max(
          ...axis.axisValues.map((subAxis) => (subAxis ? subAxis.range : 0))
        )) *
        (this.height - 2 * this.padding)
    );
  }

  updateSubAxis(index: number, subAxisId: string | null) {
    console.log(`Axis ${index} selected sub-axis: ${subAxisId}`);
    if (subAxisId) {
      this.axes[index].categoryActive = subAxisId;
    }
  }

  filteredAxisValues(axis: Axis) {
    // Returns the filtered axis values based on the active category
    return axis.axisValues.filter(
      (subAxis) => subAxis && subAxis.id === axis.categoryActive
    );
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
svg {
  border: 1px solid #ccc;
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
  stroke-width: 1px;
  //stroke: var(--color-dark-contrast);
  opacity: 60%;
  transition: stroke 0.2s ease, stroke-width 0.2s ease, opacity 0.2s ease,
    transform 1s ease;
}

.dataLineHover:hover + .dataLine {
  stroke-width: 3px;
  opacity: 100%;
}

.averageDataLine {
  pointer-events: none;
  stroke-width: 5px;
  stroke: var(--color-evaluating);
  opacity: 100%;
}
</style>
