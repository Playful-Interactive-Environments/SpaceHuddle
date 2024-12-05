<template>
  <table class="highscore-table" v-if="chartData.length > 0">
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
      @click="participantSelectionChanged(entry.participant.id)"
      :class="{
        participantSelected: participantId === entry.participant.id,
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
        class="valueTableEntry el-rate--large"
      >
        <span v-if="value.id !== 'rate' && value.id !== 'stars'">{{
          value.value != null
            ? Math.round((value.value + Number.EPSILON) * 100) / 100
            : '---'
        }}</span
        ><span v-else
          ><el-rate
            v-if="value.value != null"
            v-model="value.value"
            size="large"
            :max="3"
            :disabled="true"
          />
          <p v-else>---</p></span
        >
      </td>
    </tr>
    <tr
      v-if="highScoreCount < chartData.length"
      @click="highScoreCount = chartData.length"
    >
      <td>
        <el-button link class="text-button valueTableEntry"
          ><font-awesome-icon :icon="['fas', 'angle-down']"
        /></el-button>
      </td>
    </tr>
    <tr v-if="highScoreCount === chartData.length" @click="highScoreCount = 5">
      <td>
        <el-button link class="text-button valueTableEntry">
          <font-awesome-icon :icon="['fas', 'angle-up']"
        /></el-button>
      </td>
    </tr>
  </table>
  <p v-else>No valid data for this task</p>
</template>

<script lang="ts">
import { Prop, Watch } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ParticipantInfo } from '@/types/api/Participant';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

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
  sortColumn = 'rate';
  sortOrder = 1;
  highScoreCount = 5;

  participantId = '';

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
    if (this.participantData?.length) {
      this.chartData = this.participantData
        .filter((entry) => {
          const moduleAxis = entry.axes.find(
            (a) => a.moduleId === this.moduleId
          );
          return moduleAxis?.axisValues.some((value) => value.value != null);
        })
        .map((entry) => ({
          ...entry,
          axes: entry.axes
            .filter((axis) => axis.moduleId === this.moduleId)
            .map((axis) => ({
              ...axis,
              axisValues: axis.axisValues.sort((a, b) => {
                const aIsLast = ['stars', 'rate'].includes(a.id);
                const bIsLast = ['stars', 'rate'].includes(b.id);
                return aIsLast === bIsLast ? 0 : aIsLast ? 1 : -1;
              }),
            })),
        }));

      this.sortData();
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

        const aValue = aVal?.value;
        const bValue = bVal?.value;

        if (aValue === null || aValue === undefined) {
          return this.sortOrder === 1 ? 1 : -1;
        }
        if (bValue === null || bValue === undefined) {
          return this.sortOrder === 1 ? -1 : 1;
        }
        const primaryComparison = (bValue - aValue) * this.sortOrder;
        if (primaryComparison === 0) {
          return a.participant.id.localeCompare(b.participant.id);
        }

        return primaryComparison;
      });
    }
    return this.chartData;
  }

  @Watch('selectedParticipantId', { immediate: true })
  onSelectedParticipantIdChanged(): void {
    this.participantId = this.selectedParticipantId;
  }

  participantSelectionChanged(id: string) {
    this.participantId = this.participantId !== id ? id : '';
    this.$emit('participantSelected', this.participantId);
  }
}
</script>

<style lang="scss" scoped>
.highscore-table {
  color: var(--color-playing);
  width: 100%;
  height: auto;
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
