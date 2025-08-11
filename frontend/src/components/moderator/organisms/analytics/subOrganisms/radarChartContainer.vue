<template>
  <div class="radarChartContainer">
    <el-dropdown
      @command="(command) => handleActiveLabelUpdate(command)"
      trigger="click"
      placement="bottom"
      @click="
        participantSelectionChanged(
          getDatasetsOfLabel(labels.indexOf(activeLabel)).map(
            (set) => set.avatar.id
          )
        )
      "
      split-button
    >
      <div class="el-dropdown-link">
        <div class="el-dropdown-item-text">
          <p class="oneLineText heading el-dropdown-item-title">
            {{
              activeLabel === ''
                ? $t('moderator.organism.analytics.radarCharts.noTypeSelected')
                : $t(
                    `module.information.personalityTest.${test}.result.${activeLabel}.name`
                  )
            }}
          </p>
          <p>
            {{ getDatasetsOfLabel(labels.indexOf(activeLabel)).length }}
            <font-awesome-icon icon="user" />
          </p>
        </div>
      </div>
      <template #dropdown>
        <el-dropdown-menu>
          <el-dropdown-item class="oneLineText" :command="''">
            {{ $t('moderator.organism.analytics.radarCharts.noTypeSelected') }}
          </el-dropdown-item>
          <el-dropdown-item
            v-for="label in labels"
            :key="label"
            :command="label"
          >
            <div class="el-dropdown-item-text">
              <p class="oneLineText el-dropdown-item-title">
                {{
                  $t(
                    `module.information.personalityTest.${test}.result.${label}.name`
                  )
                }}
              </p>
              <p>
                {{ getDatasetsOfLabel(labels.indexOf(label)).length }}
                <font-awesome-icon icon="user" />
              </p>
            </div>
          </el-dropdown-item>
        </el-dropdown-menu>
      </template>
    </el-dropdown>
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
    <div class="radar-chart">
      <radar-chart
        :labels="labels"
        :datasets="
          getDatasetsOfLabel(labels.indexOf(activeLabel)).filter((set) =>
            selectedParticipantIds.includes(set.avatar.id)
          )
        "
        :test="test"
        :title="title"
        :size="size"
        :levels="levels"
        :defaultColor="defaultColor"
        :range="minMaxValues"
        :filterClass="filterClass"
        v-model:selectedParticipantIds="participantIds"
        @participant-selected="participantSelectionChanged"
      />
    </div>
    <div class="participant-radar-container">
      <div
        v-for="data in getDatasetsOfLabel(labels.indexOf(activeLabel))"
        :key="data.avatar.id + 'radar'"
        :style="{
          opacity: selectedParticipantIds.includes(data.avatar.id) ? 1 : 0.3,
        }"
        @click="addToParticipantSelection(data.avatar.id)"
        class="participant-radar"
      >
        <font-awesome-icon
          :icon="data.avatar.symbol"
          class="avatar-icon"
          :style="{
            color: selectedParticipantIds.includes(data.avatar.id)
              ? data.avatar.color
              : defaultColor,
            fontSize: 'var(--font-size-small)',
          }"
        />
        <radar-chart
          :labels="labels"
          :datasets="[data]"
          :test="test"
          :title="title"
          :size="size * 0.15"
          :levels="2"
          :defaultColor="
            selectedParticipantIds.includes(data.avatar.id)
              ? data.avatar.color
              : defaultColor
          "
          :range="minMaxValues"
          :filterClass="filterClass"
          :show-average="false"
          :show-labels="false"
          :opacity="false"
        />
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Avatar } from '@/types/api/Participant';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import TaskType from '@/types/enum/TaskType';
import RadarChart from '@/components/moderator/organisms/analytics/subOrganisms/radarChart.vue';

interface RadarDataSet {
  data: number[];
  avatar: Avatar;
}

@Options({
  components: {
    RadarChart,
    ToolTip,
  },
  emits: ['update:selectedParticipantIds'],
})
export default class RadarChartContainer extends Vue {
  @Prop({ type: Array, required: true }) labels!: string[];
  @Prop({ default: () => '' }) test!: string;
  @Prop({ default: () => '' }) title!: string;
  @Prop({ required: true }) datasets!: RadarDataSet[];
  @Prop({ type: Number, default: 300 }) size!: number;
  @Prop({ type: Number, default: 5 }) levels!: number;
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];
  @Prop({ type: Number, default: 5 }) defaultColor!: string;

  taskType = TaskType.INFORMATION;

  filterClass = 'primary';
  activeLabel = '';

  participantIds: string[] = [];

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

  handleActiveLabelUpdate(label: string): void {
    this.activeLabel = label;
  }

  addToParticipantSelection(id: string): void {
    if (this.participantIds.includes(id)) {
      this.participantIds = this.participantIds.filter((pid) => pid !== id);
    } else {
      this.participantIds.push(id);
    }
    this.$emit('update:selectedParticipantIds', this.participantIds);
  }

  @Watch('selectedParticipantIds', { immediate: true })
  onSelectedParticipantIdsChanged(): void {
    this.participantIds = this.selectedParticipantIds;
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

  getDatasetsOfLabel(index: number): RadarDataSet[] {
    if (index === -1) {
      return this.datasets;
    }
    switch (this.filterClass) {
      case '':
        return this.datasets;
      case 'primary':
        return this.getParticipantsOfPrimaryClass(index);
      case 'secondary':
        return this.getParticipantsOfSecondaryClass(index);
      case 'exception':
        return this.getParticipantsOfExceptionClass(index);
    }
    return this.getParticipantsOfPrimaryClass(index);
  }

  getParticipantsOfPrimaryClass(index: number): RadarDataSet[] {
    const participants: RadarDataSet[] = [];

    for (const entry of this.datasets) {
      if (entry.data[index] >= Math.max(...entry.data)) {
        participants.push(entry);
      }
    }
    return participants;
  }

  getParticipantsOfSecondaryClass(index: number): RadarDataSet[] {
    const participants: RadarDataSet[] = [];

    for (const entry of this.datasets) {
      const value = entry.data[index];
      const sortedData = [...entry.data].sort((a, b) => b - a);
      const secondMaxValue = sortedData[1];

      if (value === secondMaxValue) {
        participants.push(entry);
      }
    }
    return participants;
  }

  getParticipantsOfExceptionClass(index: number): RadarDataSet[] {
    const participants: RadarDataSet[] = [];

    for (const entry of this.datasets) {
      if (entry.data[index] <= Math.min(...entry.data)) {
        participants.push(entry);
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
  gap: 1rem;
}

.participant-radar-container {
  position: relative;
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  justify-content: center;
  align-items: center;
  gap: 2rem;
}

.participant-radar {
  cursor: pointer;
  transition: transform 0.3s ease, opacity 0.3s ease;

  &:hover {
    transform: scale(1.15);
    opacity: 0.7;
  }
}

.grid-polygon {
  fill: none;
  stroke: var(--color-background-darker);
  stroke-width: 0.5;
  will-change: transform;
}

.axis-line {
  stroke: var(--color-background-darker);
  stroke-width: 0.5;
  will-change: transform;
}

.radar-polygon {
  transition: all 0.5s ease;
  will-change: fill-opacity, stroke-opacity;
}

.average-radar-polygon {
  fill: url(#diagonalHatch);
  stroke: var(--color-evaluating);
  stroke-width: 1.5;
  stroke-linejoin: round;
  stroke-linecap: round;
}

.hatch-path {
  stroke: var(--color-evaluating);
  stroke-width: 0.6;
}

.radar-label {
  text-align: center;
  display: flex;
  align-items: center;
  justify-content: center;
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
  padding: 0.2rem;
}

.classSelection {
  font-size: var(--font-size-small);
}

.el-dropdown-link {
  width: 15rem;
  .heading {
    margin: unset;
  }
}

.el-dropdown-item-text {
  display: flex;
  justify-content: space-between;
  align-items: center;
  width: 100%;
  .el-dropdown-item-title {
    width: 80%;
  }
}

.selectableParticipants {
  position: absolute;
  top: -1.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 0.2rem;
  background-color: white;
  border-radius: var(--border-radius-small);
  padding: 0.1rem;
}
</style>
