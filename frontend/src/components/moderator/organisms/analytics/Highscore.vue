<template>
  <table class="highscore-table" v-if="chartData.length > 0">
    <thead>
      <tr>
        <th />
        <th />
        <th
          v-for="entry in chartData[0].values"
          :key="entry.id"
          @click="setSortColumn(entry.id)"
          :style="{
            cursor: 'pointer',
            width: `${93 / chartData[0].values.length}%`,
          }"
        >
          <ToolTip :content="getTranslation(entry.id)" :show-after="500">
            <span class="twoLineText">
              {{ getTranslation(entry.id) }}
              <font-awesome-icon
                :icon="sortIcon(entry.id)"
                v-if="entry.id === sortColumn"
              />
            </span>
          </ToolTip>
        </th>
      </tr>
    </thead>
    <tbody>
      <tr
        v-for="(entry, index) in displayedChartData"
        :key="entry.participant.id"
        class="participantTableEntries"
        @click="participantSelectionChanged(entry.participant.id)"
        :class="{ participantSelected: isSelected(entry.participant.id) }"
      >
        <td>{{ index + 1 }}.</td>
        <td>
          <font-awesome-icon
            :icon="entry.participant.avatar.symbol"
            :style="{ color: entry.participant.avatar.color }"
          ></font-awesome-icon>
        </td>
        <td
          v-for="value in entry.values"
          :key="value.id"
          class="valueTableEntry el-rate--large"
        >
          <span
            v-if="
              value.id !== 'rate' &&
              value.id !== 'stars' &&
              value.id !== 'ideas'
            "
          >
            {{ formatValue(value) }}
            {{ getUnit(value) }}
          </span>
          <span v-else-if="value.id === 'rate' || value.id === 'stars'">
            <el-rate
              v-if="value.value != null"
              v-model="value.value"
              size="large"
              :max="3"
              :disabled="true"
            />
            <span v-else>---</span>
          </span>
          <span v-else-if="value.id === 'ideas'">
            <Gallery
              v-if="value.ideas && value.ideas.length > 0"
              :task-id="taskId"
              :ideas="value.ideas"
              :time-modifier="1"
            />
            <span v-else>---</span>
          </span>
          <span v-else>---</span>
        </td>
      </tr>
      <tr
        v-if="highScoreCount < chartData.length"
        @click="highScoreCount = chartData.length"
      >
        <td>
          <el-button link class="text-button valueTableEntry">
            <font-awesome-icon :icon="['fas', 'angle-down']" />
          </el-button>
        </td>
      </tr>
      <tr
        v-if="highScoreCount === chartData.length"
        @click="highScoreCount = 5"
      >
        <td>
          <el-button link class="text-button valueTableEntry">
            <font-awesome-icon :icon="['fas', 'angle-up']" />
          </el-button>
        </td>
      </tr>
    </tbody>
  </table>
  <p v-else>No valid data for this task</p>
</template>

<script lang="ts">
import { Prop, Watch } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { ParticipantInfo } from '@/types/api/Participant';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import Gallery from '@/modules/common/visualisation_master/organisms/gallery.vue';
import { Idea } from '@/types/api/Idea';

export interface HighScoreEntry {
  participant: ParticipantInfo;
  values: { id: string; value: number | null; ideas?: Idea[] | null }[];
}

@Options({
  components: { Gallery, ToolTip, FontAwesomeIcon },
  emits: ['update:selectedParticipantIds'],
})
export default class Highscore extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly tableData!:
    | HighScoreEntry[]
    | TaskParticipantIterationStep[];
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];
  @Prop({ default: () => '' }) translationPath!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;

  sortColumn = 'stars';
  sortOrder = 1;
  highScoreCount = 5;
  chartData: HighScoreEntry[] = [];

  get displayedChartData() {
    return this.chartData.slice(0, this.highScoreCount);
  }

  setSortColumn(column: string): void {
    if (this.sortColumn === column) {
      this.sortOrder *= -1;
    } else {
      this.sortOrder = 1;
    }
    this.sortColumn = column;
    this.sortData();
  }

  @Watch('tableData', { immediate: true })
  @Watch('taskId', { immediate: true })
  onChartDataChanged(): void {
    if (this.tableData?.length) {
      if (this.isHighScoreEntry(this.tableData[0])) {
        this.chartData = this.tableData as HighScoreEntry[];
      } else {
        this.convertToHighScoreEntryArray(
          this.tableData as TaskParticipantIterationStep[]
        );
      }
      this.sortData();
    }
  }

  isHighScoreEntry(entry: any): boolean {
    return (
      entry.values[0]?.id !== undefined &&
      entry.values[0]?.value !== undefined &&
      entry.participant !== undefined
    );
  }

  convertToHighScoreEntryArray(data: TaskParticipantIterationStep[]): void {
    this.chartData = [];
  }

  sortData(): void {
    if (this.chartData.length >= 2) {
      this.chartData.sort((a, b) => {
        const bVal = b.values.find((value) => value.id === this.sortColumn);
        const aVal = a.values.find((value) => value.id === this.sortColumn);

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
  }

  participantSelectionChanged(id: string): void {
    const newValue = this.selectedParticipantIds.includes(id)
      ? this.selectedParticipantIds.filter((i) => i !== id)
      : [id];
    this.$emit('update:selectedParticipantIds', newValue);
  }

  getTranslation(id: string): string {
    return this.$t(this.translationPath + id);
  }

  sortIcon(id: string): [string, string] {
    if (id === this.sortColumn) {
      return this.sortOrder === -1
        ? ['fas', 'angle-up']
        : ['fas', 'angle-down'];
    }
    return ['', ''];
  }

  formatValue(value: { id: string; value: number | null }): string {
    return value.value != null
      ? (Math.round((value.value + Number.EPSILON) * 100) / 100).toString()
      : '---';
  }

  getUnit(value: { id: string; value: number | null }): string {
    if (value.value != null) {
      const unit = this.$t(this.translationPath + 'units.' + value.id);
      return unit.slice(-value.id.length) !== value.id ? unit : '';
    }
    return '';
  }

  isSelected(id: string): boolean {
    return this.selectedParticipantIds.includes(id);
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
    cursor: pointer;
  }

  tr {
    text-align: left;
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
  cursor: pointer;

  &:hover {
    background-color: var(--color-background-dark);
  }
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
