<template>
  <table class="highscore-table">
    <tr>
      <th />
      <th />
      <th
        v-for="entry in rowHeaders"
        :key="this.moduleId + entry.id"
        @click="setSortColumn(entry.id)"
      >
        {{ entry.id }}
      </th>
    </tr>
    <tr
      v-for="(entry, index) in chartData.slice(0, this.highScoreCount)"
      :key="entry.participant.id + index"
    >
      <td>{{ index + 1 }}.</td>
      <td>
        <font-awesome-icon
          :icon="entry.participant.avatar.symbol"
          :style="{ color: entry.participant.avatar.color }"
        ></font-awesome-icon>
      </td>
      <td
        v-for="value in getAxisValues(index)"
        :key="entry.participant.id + value.id"
      >
        <span v-if="value.id !== 'rate' || value.id !== 'stars'">{{
          value.value
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
    <tr v-if="highScoreCount < highScoreList.length">
      <td>
        <el-button
          link
          @click="highScoreCount = highScoreList.length"
          class="text-button"
        >
          ...
        </el-button>
      </td>
    </tr>
  </table>
  test
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
  emits: ['selectionDone'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Highscore extends Vue {
  @Prop() readonly moduleId!: string;
  @Prop() readonly participantData!: DataEntry[];
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
  }

  @Watch('participantData', { immediate: true })
  onChartDataChanged(): void {
    if (this.participantData != null) {
      this.chartData = this.participantData.filter((entry) => {
        const moduleAxis = entry.axes.filter(
          (a) => a.moduleId === this.moduleId
        )[0];
        console.log(moduleAxis);
        if (moduleAxis) {
          for (const value of moduleAxis.axisValues) {
            if (value.value != null) {
              return true;
            }
          }
        }
        return false;
      });
    }
    console.log(this.chartData);
  }

  get rowHeaders(): AxisValue[] {
    return this.getAxisValues(0);
  }

  getAxisValues(index: number): AxisValue[] {
    return this.chartData[index].axes.filter(
      (axis) => axis.moduleId === this.moduleId
    )[0].axisValues;
  }
}
</script>

<style lang="scss" scoped>
.highscore-table {
  color: var(--color-playing);
  width: 100%;

  td {
    width: 25%;
  }
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
