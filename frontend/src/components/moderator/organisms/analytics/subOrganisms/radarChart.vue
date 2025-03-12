<template>
  <div class="radarChartContainer">
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
          class="grid-polygon"
        ></polygon>

        <!-- Draw the axes -->
        <line
          v-for="(label, index) in labels"
          :key="label"
          x1="50"
          y1="50"
          :x2="getAxisEnd(index, maxRadius).x"
          :y2="getAxisEnd(index, maxRadius).y"
          class="axis-line"
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
          class="radar-polygon"
        />

        <!-- Draw the average dataset polygon -->
        <polygon
          v-if="averageDataset"
          :points="getDataPoints(averageDataset.data)"
          class="average-radar-polygon"
        />
        <pattern
          id="diagonalHatch"
          patternUnits="userSpaceOnUse"
          width="4"
          height="4"
        >
          <path
            d="M-1,1 l2,-2
           M0,4 l4,-4
           M3,5 l2,-2"
            class="hatch-path"
          />
        </pattern>
      </svg>
      <ToolTip
        v-for="(label, index) in labels"
        :key="'label-' + index"
        :show-after="200"
        :effect="'light'"
        :disabled="getParticipantsOfFilterClass(index).length <= 0"
      >
        <template #content>
          <font-awesome-icon
            v-for="avatar of getParticipantsOfFilterClass(index)"
            :key="avatar.id"
            :icon="avatar.symbol"
            class="avatar-icon"
            :style="{
              color: avatar.color,
            }"
          ></font-awesome-icon>
        </template>
        <div
          :style="getLabelPosition(index)"
          :class="{
            'radar-label': true,
            'radar-label-hover': getParticipantsOfFilterClass(index).length > 0,
          }"
          @click="
            participantSelectionChanged(
              getParticipantsOfFilterClass(index).map((avatar) => avatar.id)
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
          <p v-if="getParticipantsOfFilterClass(index).length > 0">
            {{ getParticipantsOfFilterClass(index).length }}
            <font-awesome-icon icon="user" />
          </p>
        </div>
      </ToolTip>
    </div>
    <el-radio-group v-model="filterClass" class="classSelection">
      <el-radio-button :label="'primary'" :value="'primary'">{{
        $t('moderator.organism.analytics.radarCharts.primary')
      }}</el-radio-button>
      <el-radio-button :label="'secondary'" :value="'secondary'">{{
        $t('moderator.organism.analytics.radarCharts.secondary')
      }}</el-radio-button>
      <el-radio-button :label="'exception'" :value="'exception'">{{
        $t('moderator.organism.analytics.radarCharts.exception')
      }}</el-radio-button>
    </el-radio-group>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Avatar } from '@/types/api/Participant';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import TaskType from '@/types/enum/TaskType';

@Options({
  components: {
    ToolTip,
  },
  emits: ['update:selectedParticipantIds'],
})
export default class RadarChart extends Vue {
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

  filterClass = 'primary';

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
    const range = max - min || 1;
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

  getParticipantsOfFilterClass(index: number): Avatar[] {
    switch (this.filterClass) {
      case 'primary':
        return this.getParticipantsOfPrimaryClass(index);
      case 'secondary':
        return this.getParticipantsOfSecondaryClass(index);
      case 'exception':
        return this.getParticipantsOfExceptionClass(index);
    }
    return this.getParticipantsOfPrimaryClass(index);
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

  getParticipantsOfSecondaryClass(index: number): Avatar[] {
    const participants: Avatar[] = [];

    for (const entry of this.normalizedDatasets) {
      const value = entry.data[index];
      const sortedData = [...entry.data].sort((a, b) => b - a);
      const secondMaxValue = sortedData[1];

      if (value === secondMaxValue) {
        participants.push(entry.avatar);
      }
    }
    return participants;
  }

  getParticipantsOfExceptionClass(index: number): Avatar[] {
    const participants: Avatar[] = [];

    for (const entry of this.normalizedDatasets) {
      if (entry.data[index] <= Math.min(...entry.data)) {
        participants.push(entry.avatar);
      }
    }
    return participants;
  }
}
</script>

<style lang="scss" scoped>
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

.grid-polygon {
  fill: none;
  stroke: #ccc;
  stroke-width: 0.2;
  will-change: transform;
}

.axis-line {
  stroke: #666;
  stroke-width: 0.2;
  will-change: transform;
}

.radar-polygon {
  transition: all 0.5s ease;
  will-change: fill-opacity, stroke-opacity;
}

.average-radar-polygon {
  fill: url(#diagonalHatch);
  stroke: var(--color-evaluating);
  stroke-width: 1;
  stroke-linejoin: round;
  stroke-linecap: round;
}

.hatch-path {
  stroke: var(--color-evaluating);
  stroke-width: 0.3;
}

.radar-label {
  text-align: center;
  font-size: var(--font-size-xsmall);
  color: var(--color-dark-contrast);
  font-weight: var(--font-weight-default);
  transition: all 0.3s ease;
}

.radar-label-hover:hover {
  cursor: pointer;
  color: var(--color-informing-dark);
}

.avatar-icon {
  color: var(--color-dark-contrast);
  font-size: var(--font-size-large);
  margin: 0 0.2rem;
  transition: color 0.3s ease;
}

.classSelection {
  font-size: var(--font-size-small);
}
</style>
