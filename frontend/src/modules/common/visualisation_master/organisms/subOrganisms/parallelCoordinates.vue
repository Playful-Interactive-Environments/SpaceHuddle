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
        <!--        <g
          v-for="(subAxis, subAxisIndex) in filteredAxisValues(axis)"
          :key="subAxis.id"
        >
          <line
            :y1="getLabelPosition(subAxisIndex, axis)"
            :y2="getLabelPosition(subAxisIndex, axis)"
            :x2="5"
            stroke="black"
          />
          <text :y="getLabelPosition(subAxisIndex, axis)" x="10" font-size="10">
            {{ subAxis.range }}
          </text>
        </g>-->
      </g>

      <!-- Data Lines as Curves -->
      <g v-for="entry in chartData" :key="entry.participant.id">
        <path class="dataLineHover" :d="getDataLine(entry)" fill="none" />
        <path
          class="dataLine"
          :d="getDataLine(entry)"
          fill="none"
          :stroke="entry.participant.avatar.color"
        />
      </g>
    </svg>

    <!-- Dropdown Menus -->
    <div
      v-for="(axis, index) in activeAxes"
      :key="axis.moduleId"
      :style="{
        position: 'absolute',
        left: `${axesSpacing * index}px`,
        top: '10px', // Adjust this to position the dropdown as needed
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

  axes: Axis[] = [];
  chartData: DataEntry[] = [];

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
        axisValues: axis.axisValues.filter((subAxis) => subAxis !== null), // Filter out any null subAxis
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

  getLabelPosition(index: number, axis: Axis) {
    return (
      this.padding +
      (index * (this.height - 2 * this.padding)) / (axis.axisValues.length - 1)
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
  opacity: 40%;
  transition: stroke 0.2s ease, stroke-width 0.2s ease, opacity 0.2s ease,
    transform 1s ease;
}

.dataLineHover:hover + .dataLine {
  stroke-width: 3px;
  stroke: red;
  opacity: 100%;
}
</style>
