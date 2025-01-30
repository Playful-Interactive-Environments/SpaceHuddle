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
          v-for="level in gridLevels"
          :key="level"
          :points="getPolygonPoints(level)"
          fill="none"
          stroke="#ccc"
          stroke-width="0.2"
        ></polygon>

        <!-- Draw the axes -->
        <line
          v-for="(label, index) in labels"
          :key="label"
          x1="50"
          y1="50"
          :x2="getAxisEnd(index, maxRadius).x"
          :y2="getAxisEnd(index, maxRadius).y"
          stroke="#666"
          stroke-width="0.2"
        ></line>

        <!-- Draw the data polygons -->
        <polygon
          v-for="dataset in normalizedDatasets"
          :key="dataset.avatar.id"
          :points="getDataPoints(dataset.data)"
          :fill="datasetColors[dataset.avatar.id]"
          :fill-opacity="datasetOpacities[dataset.avatar.id]"
          :stroke="datasetColors[dataset.avatar.id]"
          :stroke-opacity="datasetOpacities[dataset.avatar.id] + 0.2"
          stroke-width="0.5"
          class="radarPolygon"
        />

        <!-- Draw the average dataset polygon -->
        <polygon
          v-if="averageDataset"
          :points="getDataPoints(averageDataset.data)"
          :fill="'transparent'"
          :fill-opacity="averageDatasetOpacity + 0.1"
          :stroke="'var(--color-evaluating)'"
          :stroke-width="1"
          class="radarPolygon averageRadarPolygon"
        />
      </svg>
      <ToolTip
        v-for="(label, index) in labels"
        :key="'label-' + index"
        :show-after="200"
        :effect="'light'"
        :disabled="getParticipantsOfPrimaryClass(index).length <= 0"
      >
        <template #content>
          <font-awesome-icon
            v-for="avatar of getParticipantsOfPrimaryClass(index)"
            :key="avatar.id"
            :icon="avatar.symbol"
            :style="{
              color: avatar.color,
              fontSize: 'var(--font-size-large)',
              margin: '0 0.2rem',
            }"
          ></font-awesome-icon>
        </template>
        <div
          :style="getLabelPosition(index)"
          class="radar-label"
          @click="
            participantSelectionChanged(
              getParticipantsOfPrimaryClass(index).map((avatar) => avatar.id)
            )
          "
        >
          <p class="twoLineText radar-label-text">
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
      </ToolTip>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Avatar } from '@/types/api/Participant';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import TaskType from '@/types/enum/TaskType';

@Options({
  components: {
    ToolTip,
  },
  emits: ['update:selectedParticipantIds'],
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
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];
  @Prop({ type: Number, default: 5 }) defaultColor!: string;

  taskType = TaskType.INFORMATION;
  normalizedDatasets: { data: number[]; avatar: Avatar }[] = [];

  getColorOfType(taskType: TaskType) {
    return getColorOfType(taskType);
  }

  getIconOfType(taskType: TaskType) {
    return getIconOfType(taskType);
  }

  get minMaxValues(): { min: number; max: number } {
    const allData = this.datasets.flatMap((dataset) => dataset.data);
    const min = Math.min(...allData);
    const max = Math.max(...allData);
    return { min: min < 0 ? min : 0, max };
  }

  get minValue(): number {
    return this.minMaxValues.min;
  }

  get maxValue(): number {
    return this.minMaxValues.max;
  }

  created() {
    this.updateNormalizedDatasets();
  }

  participantSelectionChanged(ids: string[] | null) {
    if (ids) {
      if (JSON.stringify(this.selectedParticipantIds) === JSON.stringify(ids)) {
        this.$emit('update:selectedParticipantIds', []);
      } else {
        this.$emit('update:selectedParticipantIds', ids);
      }
    }
  }

  @Watch('datasets', { immediate: true, deep: true })
  updateNormalizedDatasets() {
    this.normalizedDatasets = this.datasets
      .map((dataset) => ({
        ...dataset,
        data: this.normalizeData(dataset.data),
      }))
      .sort(
        (a, b) =>
          b.data.reduce(
            (acc: number, current: number): number => acc + current,
            0
          ) -
          a.data.reduce(
            (acc: number, current: number): number => acc + current,
            0
          )
      );
  }

  get datasetColors(): Record<string, string> {
    return this.datasets.reduce((acc, dataset) => {
      acc[dataset.avatar.id] = this.getColor(dataset);
      return acc;
    }, {} as Record<string, string>);
  }

  get averageDatasetOpacity(): number {
    if (!this.averageDataset) return 0;
    return this.calculateOpacity(this.averageDataset);
  }

  get datasetOpacities(): Record<string, number> {
    return this.datasets.reduce((acc, dataset) => {
      acc[dataset.avatar.id] = this.calculateOpacity(dataset);
      return acc;
    }, {} as Record<string, number>);
  }

  getColor(dataset: { data: unknown[]; avatar: Avatar }): string {
    if (this.selectedParticipantIds.includes(dataset.avatar.id)) {
      return dataset.avatar.color;
    }
    return this.defaultColor;
  }

  calculateOpacity(dataset: { data: unknown[]; avatar: Avatar }): number {
    if (this.selectedParticipantIds.includes(dataset.avatar.id)) {
      return 0.8;
    } else if (
      !this.selectedParticipantIds.length ||
      !this.datasets.find((dataset) =>
        this.selectedParticipantIds.includes(dataset.avatar.id)
      )
    ) {
      return 0.25;
    }
    return 0.05;
  }

  normalizeData(data: number[]): number[] {
    const { min, max } = this.minMaxValues;
    const range = max - min || 1; // Prevent division by zero
    return data.map((value) => ((value - min) / range) * 100);
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
      this.normalizedDatasets.find((dataset) =>
        this.selectedParticipantIds.includes(dataset.avatar.id)
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
  font-weight: var(--font-weight-default);
  transition: all 0.3s ease;
  cursor: pointer;
  text-shadow: transparent;
}

.radar-label:hover {
  color: var(--color-informing-dark);
}

.radarPolygon {
  transition: all 0.5s ease;
}

.averageRadarPolygon {
  stroke-linejoin: round;
  stroke-linecap: round;
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
