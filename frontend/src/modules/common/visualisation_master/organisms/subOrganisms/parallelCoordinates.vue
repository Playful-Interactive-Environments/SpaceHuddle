<template>
  <svg :width="width" :height="height">
    <!-- Axes -->
    <g
      class="axes"
      v-for="(axis, index) in templateAxes.filter((ax) => ax.active)"
      :key="axis.id"
      :transform="`translate(${axesSpacing * index}, 0)`"
    >
      <!-- Axis Line -->
      <line :y1="padding" :y2="height - padding" stroke="black" />

      <!-- Tick Marks and Labels -->
      <g v-for="(label, labelIndex) in axis.labels" :key="labelIndex">
        <line
          :y1="getLabelPosition(labelIndex, axis)"
          :y2="getLabelPosition(labelIndex, axis)"
          :x2="5"
          stroke="black"
        />
        <text :y="getLabelPosition(labelIndex, axis)" x="10" font-size="10">
          {{ label }}
        </text>
      </g>
    </g>

    <!-- Data Lines as Curves -->
    <g v-for="(entry, entryIndex) in templateChartData" :key="entryIndex">
      <path class="dataLineHover" :d="getDataLine(entry)" fill="none" />
      <path
        class="dataLine"
        :d="getDataLine(entry)"
        fill="none"
        :stroke-dasharray="entry.missing ? '4,4' : '0'"
      />
    </g>
  </svg>
  <el-button @click="this.templateAxes[2].active = !this.templateAxes[2].active"
    >switch Axis</el-button
  >
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

interface Axis {
  id: string;
  name: string;
  labels: string[];
  active: boolean;
}

interface DataEntry {
  values: (number | null)[];
  color?: string;
  missing?: boolean;
}

export default class Analytics extends Vue {
  @Prop({ default: [] }) axes!: Axis[];
  @Prop({ default: [] }) chartData!: DataEntry[];
  width = 800;
  height = 500;
  padding = 20;

  templateAxes: Axis[] = [
    {
      id: 'Axis1',
      name: 'test Axis 1',
      labels: ['Low', 'Medium', 'High', 'Higher'],
      active: true,
    },
    {
      id: 'Axis2',
      name: 'test Axis 2',
      labels: ['1', '2', '3', '4'],
      active: true,
    },
    { id: 'Axis3', name: 'test Axis 3', labels: ['A', 'B', 'C'], active: true },
    {
      id: 'Axis4',
      name: 'test Axis 4',
      labels: ['20', '40', '60', '80', '100'],
      active: true,
    },
  ];

  templateChartData: DataEntry[] = [
    { values: [0.12, 0.56, 0.75, 0.34], color: 'red' },
    { values: [0.45, null, 0.67, 0.89], missing: true },
    { values: [0.21, 0.79, 0.32, 0.11], color: 'blue' },
    { values: [0.92, 0.58, null, 0.4], missing: true },
    { values: [0.37, 0.2, 0.64, 0.53], color: 'green' },
    { values: [0.09, 0.27, 0.15, 0.88], color: 'orange' },
    { values: [null, 0.65, 0.44, 0.22], missing: true },
    { values: [0.34, 0.41, 0.33, 0.11], color: 'purple' },
    { values: [0.76, null, 0.58, 0.29], missing: true },
    { values: [0.82, 0.63, 0.39, 0.91], color: 'cyan' },
  ];

  get axesSpacing(): number {
    return this.width / this.templateAxes.filter((axis) => axis.active).length;
  }

  getDataLine(entry: DataEntry) {
    //filter active data and axes
    const activeAxes = this.templateAxes.filter((axis) => axis.active);
    const values = entry.values.filter(
      (val, index) => this.templateAxes[index].active
    );

    const pathParts = values.map((value, index) => {
      const axis = activeAxes[index];
      const x = this.axesSpacing * index;
      const y =
        value !== null ? this.getYPosition(value, axis) : this.height / 2;

      if (index === 0) {
        // Starting point for the path
        return `M${x},${y}`;
      }

      const prevX = this.axesSpacing * (index - 1);
      const prevY =
        values[index - 1] !== null
          ? this.getYPosition(values[index - 1]!, activeAxes[index - 1])
          : this.height / 2;

      // Horizontal control points for smooth curves
      const controlX1 = prevX + this.axesSpacing / 2;
      const controlX2 = x - this.axesSpacing / 2;

      return `C${controlX1},${prevY} ${controlX2},${y} ${x},${y}`;
    });

    return pathParts.join(' ');
  }

  getLabelPosition(index: number, axis: Axis) {
    return (
      this.padding +
      (index * (this.height - 2 * this.padding)) / (axis.labels.length - 1)
    );
  }

  getYPosition(value: number, axis: Axis) {
    return this.padding + value * (this.height - 2 * this.padding);
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
  stroke: var(--color-dark-contrast);
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
