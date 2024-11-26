<template>
  <table class="highscore-table" v-if="chartData.length > 0">
    <p v-if="chartData[0].axes[0].axisValues === undefined">test</p>
    <tr>
      <th />
      <th />
      <th
        v-for="entry in chartData[0].axes[0].axisValues"
        :key="this.moduleId + entry.id"
        @click="setSortColumn(entry.id)"
        :style="{ cursor: 'pointer' }"
      >
        {{ entry.id }}
        <font-awesome-icon
          :icon="['fas', 'angle-up']"
          v-if="entry.id === sortColumn && sortOrder === -1"
        />
        <font-awesome-icon
          :icon="['fas', 'angle-down']"
          v-if="entry.id === sortColumn && sortOrder === 1"
        />
      </th>
    </tr>
    <tr
      v-for="(entry, index) in chartData.slice(0, this.highScoreCount)"
      :key="entry.participant.id + index"
      class="participantTableEntries"
      @click="$emit('participantSelected', entry.participant.id)"
      :class="{
        participantSelected:
          selectedParticipantId === entry.participant.id,
      }"
    >
      <td>{{ index + 1 }}.</td>
      <td>
        <font-awesome-icon
          :icon="entry.participant.avatar.symbol"
          :style="{ color: entry.participant.avatar.color }"
        ></font-awesome-icon>
      </td>
      <td
        v-for="value in chartData[index].axes[0].axisValues"
        :key="entry.participant.id + value.id"
        class="valueTableEntry"
      >
        <span v-if="value.id !== 'rate' && value.id !== 'stars'">{{
          Math.round((value.value + Number.EPSILON) * 100) / 100
        }}</span
        ><span v-else
          ><el-rate
            v-model="value.value"
            size="large"
            :max="3"
            :disabled="true"
        /></span>
      </td>
    </tr>
    <tr v-if="highScoreCount < chartData.length">
      <td>
        <el-button
          link
          @click="highScoreCount = chartData.length"
          class="text-button"
        >
          ...
        </el-button>
      </td>
    </tr>
  </table>
  <p v-else>No valid data for this task</p>
</template>

<script lang="ts">
import { Prop, Watch } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Avatar, ParticipantInfo } from '@/types/api/Participant';
import * as votingService from '@/services/voting-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import { VoteParameterResult } from '@/types/api/Vote';

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

@Options({
  components: { FontAwesomeIcon },
  emits: ['participantSelected'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Highscore extends Vue {
  @Prop() readonly moduleId!: string;
  @Prop() readonly participantData!: DataEntry[];
  @Prop() readonly selectedParticipantId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  highScoreList: { value: number | any; avatar: Avatar }[] = [];
  sortColumn = 'rate';
  sortOrder = 1;
  highScoreCount = 5;

  chartData: DataEntry[] = [];

  setSortColumn(column: string): void {
    if (this.sortColumn === column) this.sortOrder *= -1;
    else this.sortOrder = 1;
    this.sortColumn = column;
    this.sortData();
  }

  @Watch('participantData', { immediate: true })
  @Watch('moduleId', { immediate: true })
  onChartDataChanged(): void {
    if (this.participantData != null && this.participantData.length > 0) {
      this.chartData = this.participantData.filter((entry) => {
        const moduleAxis = entry.axes.filter(
          (a) => a.moduleId === this.moduleId
        )[0];
        if (moduleAxis) {
          for (const value of moduleAxis.axisValues) {
            if (value.value != null) {
              return true;
            }
          }
          return false;
        }
      });
      for (const entry of this.chartData) {
        entry.axes = entry.axes.filter(
          (axis) => axis.moduleId === this.moduleId
        );
      }
      this.chartData.forEach((entry) => {
        entry.axes.forEach((axis) => {
          axis.axisValues.sort((a, b) => {
            const aIsLast = a.id === 'stars' || a.id === 'rate';
            const bIsLast = b.id === 'stars' || b.id === 'rate';
            if (aIsLast && !bIsLast) return 1;
            if (!aIsLast && bIsLast) return -1;
            return 0;
          });
        });
      });
    }
  }

  sortData(): DataEntry[] {
    if (this.chartData.length >= 2) {
      return this.chartData.sort((a, b) => {
        const bVal = b.axes[0].axisValues.find(
          (value) => value.id === this.sortColumn
        );
        const aVal = a.axes[0].axisValues.find(
          (value) => value.id === this.sortColumn
        );

        if (
          aVal != null &&
          bVal != null &&
          aVal.value != null &&
          bVal.value != null
        ) {
          return (bVal.value - aVal.value) * this.sortOrder;
        }
        return -1;
      });
    }
    return this.chartData;
  }
}
</script>

<style lang="scss" scoped>
.highscore-table {
  color: var(--color-playing);
  width: 100%;
  height: 100%;
  th {
    padding-bottom: 0.3rem;
  }
  tr {
    border-bottom: 1px solid var(--color-background-dark);
  }
  td {
    width: auto;
    text-align: left;
    vertical-align: middle;
  }
}

.participantTableEntries {
  transition: background-color 0.15s ease;
}

.participantTableEntries:hover {
  background-color: var(--color-background-dark);
}

.participantSelected {
  background-color: var(--color-background-blue);
}

.text-button {
  min-height: unset;
  margin: unset;
  padding: unset;
}

.highscore::v-deep(.footer) {
  text-align: center;
  background-color: unset;
}

.highscore {
  --footer-height: 4rem;
}
</style>
