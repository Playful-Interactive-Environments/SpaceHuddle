<template>
  <div class="radar-chart">
    <svg :width="size" :height="size" viewBox="-15 -15 130 130">
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
        :x2="getAxisEnd(index).x"
        :y2="getAxisEnd(index).y"
        stroke="#666"
        stroke-width="0.2"
      ></line>

      <!-- Draw the data polygons -->
      <polygon
        v-for="(dataset, datasetIndex) in datasets"
        :key="'dataset-' + datasetIndex"
        :points="getDataPoints(dataset.data)"
        :fill="dataset.color"
        :fill-opacity="0.4"
        :stroke="dataset.color"
        stroke-width="0.5"
      ></polygon>

      <!-- Draw the labels -->
      <text
        v-for="(label, index) in labels"
        :key="'label-' + index"
        :x="getAxisEnd(index, 55).x"
        :y="getAxisEnd(index, 55).y"
        text-anchor="middle"
        font-size="5"
        fill="#333"
      >
        {{ label }}
      </text>
    </svg>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

export default class RadarChart extends Vue {
  // Props
  @Prop({ type: Array, required: true }) labels!: string[];
  @Prop({ type: Array, required: true }) datasets!: { data: number[]; color: string }[];
  @Prop({ type: Number, default: 300 }) size!: number;
  @Prop({ type: Number, default: 5 }) levels!: number;

  // Computed property to generate levels for the grid
  get gridLevels() {
    return Array.from({ length: this.levels }, (_, i) => (i + 1) / this.levels);
  }

  // Calculate polygon points for grid levels
  getPolygonPoints(level: number): string {
    const points = this.labels.map((_, index) => {
      const { x, y } = this.getAxisEnd(index, level * 50);
      return `${x},${y}`;
    });
    return points.join(" ");
  }

  // Get the end point of an axis
  getAxisEnd(index: number, radius = 50): { x: number; y: number } {
    const angle = (Math.PI * 2 * index) / this.labels.length - Math.PI / 2;
    return {
      x: 50 + radius * Math.cos(angle),
      y: 50 + radius * Math.sin(angle),
    };
  }

  // Get data points for a dataset
  getDataPoints(data: number[]): string {
    const points = data.map((value, index) => {
      const { x, y } = this.getAxisEnd(index, (value / 100) * 50);
      return `${x},${y}`;
    });
    return points.join(" ");
  }
}
</script>

<style scoped>
.radar-chart {
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
