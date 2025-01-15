<template>
  <div class="radar-chart" :style="{ width: size + 'px', height: size + 'px' }">
    <svg :width="size" :height="size" viewBox="0 0 100 100">
      <!-- Draw the grid -->
      <polygon
        v-for="(level, index) in gridLevels"
        :key="'grid-' + index"
        :points="getPolygonPoints(level)"
        fill="none"
        stroke="#ccc"
        stroke-width="0.2"
      ></polygon>

      <!-- Draw the axes -->
      <line
        v-for="(label, index) in labels"
        :key="'axis-' + index"
        x1="50"
        y1="50"
        :x2="getAxisEnd(index, maxRadius).x"
        :y2="getAxisEnd(index, maxRadius).y"
        stroke="#666"
        stroke-width="0.2"
      ></line>

      <!-- Draw the data polygons -->
      <polygon
        v-for="(dataset, datasetIndex) in normalizedDatasets"
        :key="'dataset-' + datasetIndex"
        :points="getDataPoints(dataset.data)"
        :fill="getColor(dataset.color)"
        :fill-opacity="0.2"
        :stroke="getColor(dataset.color)"
        :stroke-opacity="0.6"
        stroke-width="0.5"
      ></polygon>

      <!-- Draw the average dataset polygon -->
      <polygon
        v-if="averageDataset"
        :points="getDataPoints(averageDataset.data)"
        :fill="'var(--color-evaluating)'"
        fill-opacity="0.4"
        :stroke="'var(--color-evaluating)'"
        stroke-width="0.5"
      ></polygon>
    </svg>

    <!-- Draw the labels outside the SVG -->
    <div
      v-for="(label, index) in labels"
      :key="'label-' + index"
      class="radar-label twoLineText"
      :style="getLabelPosition(index)"
    >
      {{ label }}
    </div>
  </div>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

export default class RadarChart extends Vue {
  // Props
  @Prop({ type: Array, required: true }) labels!: string[];
  @Prop({ type: Array, required: true }) datasets!: {
    data: number[];
    color: string;
  }[];
  @Prop({ type: Number, default: 300 }) size!: number;
  @Prop({ type: Number, default: 5 }) levels!: number;

  @Prop({ type: Number, default: 5 }) defaultColor!: string;

  // Computed properties for normalization
  get minValue(): number {
    const min = Math.min(...this.datasets.flatMap((dataset) => dataset.data));
    return min < 0 ? min : 0;
  }

  get maxValue(): number {
    return Math.max(...this.datasets.flatMap((dataset) => dataset.data));
  }

  get normalizedDatasets() {
    return this.datasets.map((dataset) => ({
      ...dataset,
      data: this.normalizeData(dataset.data),
    }));
  }

  getColor(color: string) {
    return this.defaultColor;
  }

  // Normalize data to fit within the chart range
  normalizeData(data: number[]): number[] {
    const range = this.maxValue - this.minValue;
    return data.map((value) => ((value - this.minValue) / range) * 100);
  }

  get averageDataset() {
    if (this.datasets.length === 0) return null;

    const numDatasets = this.datasets.length;
    const numPoints = this.labels.length;

    const averageData = Array.from(
      { length: numPoints },
      (_, i) =>
        this.datasets.reduce((sum, dataset) => sum + dataset.data[i], 0) /
        numDatasets
    );

    return {
      data: this.normalizeData(averageData),
      color: 'blue', // Change this to your desired color for the average
    };
  }

  // Compute the maximum radius of the chart
  get maxRadius() {
    return 50; // Chart radius is fixed at 50
  }

  // Generate grid levels
  get gridLevels() {
    return Array.from({ length: this.levels }, (_, i) => (i + 1) / this.levels);
  }

  // Calculate points for grid levels
  getPolygonPoints(level: number): string {
    const radius = level * this.maxRadius;
    const points = this.labels.map((_, index) => {
      const { x, y } = this.getAxisEnd(index, radius);
      return `${x},${y}`;
    });
    return points.join(' ');
  }

  // Calculate axis endpoints
  getAxisEnd(index: number, radius: number): { x: number; y: number } {
    const angle = (Math.PI * 2 * index) / this.labels.length - Math.PI / 2;
    return {
      x: 50 + radius * Math.cos(angle),
      y: 50 + radius * Math.sin(angle),
    };
  }

  // Calculate data points for a dataset
  getDataPoints(data: number[]): string {
    const points = data.map((value, index) => {
      const { x, y } = this.getAxisEnd(index, (value / 100) * this.maxRadius);
      return `${x},${y}`;
    });
    return points.join(' ');
  }

  // Get label positions
  getLabelPosition(index: number): Record<string, string> {
    const radius = 60; // Slightly outside the chart
    const { x, y } = this.getAxisEnd(index, radius);

    const normalizedX = (x / 100) * this.size;
    const normalizedY = (y / 100) * this.size;

    return {
      position: 'absolute',
      left: `${normalizedX}px`,
      top: `${normalizedY}px`,
      transform: 'translate(-50%, -50%)',
      width: `${this.size / 4}px`,
    };
  }
}
</script>

<style scoped>
.radar-chart {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
}

.radar-label {
  pointer-events: none;
  text-align: center;
  font-size: var(--font-size-xsmall);
  color: var(--color-dark-contrast);
  background-color: var(--color-background);
}
</style>
