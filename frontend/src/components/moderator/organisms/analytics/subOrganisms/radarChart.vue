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
        :fill="getColor(dataset)"
        :fill-opacity="getOpacity(dataset)"
        :stroke="getColor(dataset)"
        :stroke-opacity="getOpacity(dataset) + 0.2"
        stroke-width="0.5"
        class="radarPolygon"
      ></polygon>

      <!-- Draw the average dataset polygon -->
      <polygon
        v-if="averageDataset"
        :points="getDataPoints(averageDataset.data)"
        :fill="'var(--color-evaluating)'"
        :fill-opacity="getOpacity(averageDataset) + 0.1"
        :stroke="'var(--color-evaluating)'"
        :stroke-width="getOpacity(averageDataset) + 0.2"
        class="radarPolygon"
      ></polygon>

      <!-- Draw the selected participant dataset polygon -->
      <!--      <polygon
        v-if="selectedParticipantId !== '' && selectedParticipantDataset"
        :points="getDataPoints(selectedParticipantDataset.data)"
        :fill="getColor(selectedParticipantDataset)"
        :fill-opacity="0.6"
        :stroke="getColor(selectedParticipantDataset)"
        :stroke-opacity="1"
        stroke-width="0.5"
        class="radarPolygon radarPolygonSelected"
      ></polygon>-->
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
import { Avatar } from '@/types/api/Participant';

export default class RadarChart extends Vue {
  // Props
  @Prop({ type: Array, required: true }) labels!: string[];
  @Prop({ type: Array, required: true }) datasets!: {
    data: number[];
    avatar: Avatar;
  }[];
  @Prop({ type: Number, default: 300 }) size!: number;
  @Prop({ type: Number, default: 5 }) levels!: number;
  @Prop({ default: () => '' }) selectedParticipantId!: string;
  @Prop({ type: Number, default: 5 }) defaultColor!: string;

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

  getColor(dataset: { data: unknown[]; avatar: Avatar }): string {
    if (this.selectedParticipantId === dataset.avatar.id) {
      return dataset.avatar.color;
    }
    return this.defaultColor;
  }

  getOpacity(dataset: { data: unknown[]; avatar: Avatar }): number {
    if (dataset.avatar.id === this.selectedParticipantId) {
      return 0.8;
    } else if (
      this.selectedParticipantId === '' ||
      !this.datasets.find(
        (dataset) => dataset.avatar.id === this.selectedParticipantId
      )
    ) {
      return 0.25;
    }
    return 0.05;
  }

  // Normalize data to fit within the chart range
  normalizeData(data: number[]): number[] {
    const range = this.maxValue - this.minValue;
    return data.map((value) => ((value - this.minValue) / range) * 100);
  }

  get averageDataset(): { data: number[]; avatar: Avatar } | null {
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
      avatar: { id: 'null', color: 'var(--color-evaluating)', symbol: 'null' },
    };
  }

  get selectedParticipantDataset(): { data: number[]; avatar: Avatar } | null {
    return (
      this.normalizedDatasets.find(
        (dataset) => dataset.avatar.id === this.selectedParticipantId
      ) || null
    );
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

.radarPolygon {
  transition: 0.5s ease;
}

.radarPolygonSelected {
  animation: appear 0.5s ease forwards;
}

@keyframes appear {
  0% {
    opacity: 0;
  }
  100% {
    opacity: 1;
  }
}
</style>
