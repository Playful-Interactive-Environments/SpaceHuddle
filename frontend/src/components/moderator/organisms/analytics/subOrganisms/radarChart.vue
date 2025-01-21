<template>
  <div class="radarChartContainer">
    <p v-if="title !== ''" class="heading">
      <font-awesome-icon
        class="headingIcon"
        :icon="getIconOfType(taskType)"
        :style="{
          color: getColorOfType(taskType),
        }"
      />
      {{ title }}
    </p>
    <div
      class="radar-chart"
      :style="{ width: size + 'px', height: size + 'px' }"
    >
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

      <div
        v-for="(label, index) in labels"
        :key="'label-' + index"
        class="radar-label"
        :style="getLabelPosition(index)"
        @click="getParticipantsOfPrimaryClass(index)"
      >
        <p class="twoLineText">
          {{
            $t(
              `module.information.personalityTest.${test}.result.${label}.name`
            )
          }}
        </p>
        <p>
          {{ getParticipantsOfPrimaryClass(index).length }}
          <font-awesome-icon icon="user" />
        </p>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Avatar } from '@/types/api/Participant';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import TaskType from '@/types/enum/TaskType';

@Options({
  components: {
    ToolTip,
  },
})
export default class RadarChart extends Vue {
  // Props
  @Prop({ type: Array, required: true }) labels!: string[];
  @Prop({ default: () => '' }) test!: string;
  @Prop({ default: () => '' }) title!: string;
  @Prop({ type: Array, required: true }) datasets!: {
    data: number[];
    avatar: Avatar;
  }[];
  @Prop({ type: Number, default: 300 }) size!: number;
  @Prop({ type: Number, default: 5 }) levels!: number;
  @Prop({ default: () => '' }) selectedParticipantId!: string;
  @Prop({ type: Number, default: 5 }) defaultColor!: string;

  taskType = TaskType.INFORMATION;

  getColorOfType(taskType: TaskType) {
    return getColorOfType(taskType);
  }

  getIconOfType(taskType: TaskType) {
    return getIconOfType(taskType);
  }

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

  get maxRadius() {
    return 50;
  }

  get gridLevels() {
    return Array.from({ length: this.levels }, (_, i) => (i + 1) / this.levels);
  }

  getPolygonPoints(level: number): string {
    const radius = level * this.maxRadius;
    const points = this.labels.map((_, index) => {
      const { x, y } = this.getAxisEnd(index, radius);
      return `${x},${y}`;
    });
    return points.join(' ');
  }

  getAxisEnd(index: number, radius: number): { x: number; y: number } {
    const angle = (Math.PI * 2 * index) / this.labels.length - Math.PI / 2;
    return {
      x: 50 + radius * Math.cos(angle),
      y: 50 + radius * Math.sin(angle),
    };
  }

  getDataPoints(data: number[]): string {
    const points = data.map((value, index) => {
      const { x, y } = this.getAxisEnd(index, (value / 100) * this.maxRadius);
      return `${x},${y}`;
    });
    return points.join(' ');
  }

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

  getParticipantsOfPrimaryClass(index: number): Avatar[] {
    const participants: Avatar[] = [];

    for (const entry of this.normalizedDatasets) {
      if (entry.data[index] >= Math.max(...entry.data)) {
        participants.push(entry.avatar);
      }
    }
    return participants;
  }

  getPrimaryClass(data: number[]): string {
    const maxValue = Math.max(...data);
    const maxIndex = data.findIndex((entry) => entry === maxValue);
    return this.labels[maxIndex];
  }

  getSecondaryClass(data: number[]): string {
    if (data.length < 2) {
      return '';
    }

    const maxIndex = data.indexOf(Math.max(...data));
    const filteredData = data.map((val, idx) =>
      idx === maxIndex ? -Infinity : val
    );
    const secondMaxIndex = filteredData.indexOf(Math.max(...filteredData));

    return this.labels[secondMaxIndex];
  }

  getExceptionClass(data: number[]): string {
    const minValue = Math.min(...data);
    const minIndex = data.findIndex((entry) => entry === minValue);
    return this.labels[minIndex];
  }
}
</script>

<style scoped>
.radarChartContainer {
  position: relative;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
}

.radar-chart {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  margin: 4rem;
}

.radar-label {
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

.headingIcon {
  font-size: var(--font-size-xlarge);
  cursor: pointer;
}
</style>
