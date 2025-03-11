<template>
  <div class="highscoreContainer" v-if="axes && chartData.length > 0">
    <el-card
      class="highScoreSelectionContainer"
      v-for="(axis, index) in tableArray"
      :key="axis?.taskId"
      shadow="never"
      body-style="text-align: center"
      :class="{ addOn__boarder: !axis }"
    >
      <div class="highscoreModuleSelection">
        <el-dropdown
          v-if="axes.length >= 1"
          @command="updateTableArray(index, $event)"
          trigger="click"
          placement="bottom"
        >
          <div class="el-dropdown-link">
            <font-awesome-icon
              v-if="axis"
              class="highscoreModuleIcon"
              :icon="getIconOfAxis(axis)"
              :style="{ color: getColorOfAxis(axis) }"
            />
            <p class="oneLineText highscoreModuleName">
              {{ axis ? axis.taskData.taskName : 'select task' }}
              <font-awesome-icon :icon="['fas', 'angle-down']" />
            </p>
          </div>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item
                v-for="ax in axes"
                :key="ax.taskId"
                :command="ax"
              >
                {{ ax.taskData.taskName }}
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
        <font-awesome-icon
          :icon="['fas', 'trash']"
          class="trashButton"
          v-if="axis"
          @click="removeFromTableArray(index)"
        />
      </div>
      <Highscore
        v-if="axis"
        class="highscore"
        :task-id="axis.taskId"
        :table-data="filterParticipantData(axis.taskId)"
        v-model:selectedParticipantIds="participantIds"
        @update:selected-participant-ids="updateSelectedParticipantIds"
        :translation-path="getTranslationPath(axis)"
      />
    </el-card>
    <el-card
      class="highScoreSelectionContainer addOn__boarder is-align-self-center"
      shadow="never"
      body-style="text-align: center"
    >
      <p class="TableSelectionHeadline">
        {{ $t('moderator.organism.analytics.tables.table') }}
      </p>
      <div class="highscoreModuleSelection">
        <el-dropdown
          v-if="axes.length >= 1"
          @command="addToTableArray"
          trigger="click"
          placement="bottom"
        >
          <div class="el-dropdown-link">
            <p class="oneLineText highscoreModuleName">
              {{ $t('moderator.organism.analytics.tables.selectTask') }}
              <font-awesome-icon :icon="['fas', 'angle-down']" />
            </p>
          </div>
          <template #dropdown>
            <el-dropdown-menu>
              <template v-for="(ax, index) in axes" :key="ax.taskId">
                <el-dropdown-item
                  v-if="
                    (axes[index - 1] &&
                      ax.taskData.topicOrder !==
                        axes[index - 1].taskData.topicOrder) ||
                    index === 0
                  "
                  class="heading oneLineText"
                  :divided="true"
                  :style="{
                    pointerEvents: 'none',
                    paddingBottom: '0.02rem',
                    paddingTop: '0.02rem',
                  }"
                  disabled
                >
                  {{ ax.taskData.topicName }}
                </el-dropdown-item>
                <el-dropdown-item
                  :command="ax"
                  :divided="
                    (axes[index - 1] &&
                      ax.taskData.topicOrder !==
                        axes[index - 1].taskData.topicOrder) ||
                    index === 0
                  "
                >
                  <font-awesome-icon
                    class="axisIcon"
                    :icon="getIconOfAxis(ax)"
                    :style="{
                      color: getColorOfAxis(ax),
                    }"
                  />
                  <span>&nbsp;{{ ax.taskData.taskName }}</span>
                </el-dropdown-item>
              </template>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </el-card>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import Highscore from '@/components/moderator/organisms/analytics/Highscore.vue';
import { ParticipantInfo } from '@/types/api/Participant';
import TaskType from '@/types/enum/TaskType';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import { HighScoreEntry } from '@/components/moderator/organisms/analytics/Highscore.vue';
import { Idea } from '@/types/api/Idea';

interface SubAxis {
  id: string;
  range: number;
}

interface Axis {
  taskId: string;
  taskData: {
    taskType: TaskType;
    taskName: string;
    topicName: string;
    topicOrder: number;
    moduleName: string;
    initOrder: number;
  };
  axisValues: (SubAxis | null)[];
  categoryActive: string;
  active: boolean;
  available: boolean;
}

interface AxisValue {
  id: string;
  value: number | null;
  ideas?: Idea[];
}
interface DataEntry {
  participant: ParticipantInfo;
  axes: {
    taskId: string;
    axisValues: AxisValue[];
  }[];
}

@Options({
  components: {
    Highscore,
  },
  emits: ['update:selectedParticipantIds'],
})
export default class Tables extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly axes!: Axis[];
  @Prop() readonly participantData!: DataEntry[];
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;

  chartData: DataEntry[] = [];
  tableArray: (Axis | null)[] = [];
  participantIds: string[] = [];

  @Watch('participantData', { immediate: true })
  onChartDataChanged(): void {
    if (this.participantData) {
      this.chartData = this.participantData.filter((entry) =>
        entry.axes.some((axis) =>
          axis.axisValues.some((value) => value.value != null)
        )
      );
    }
  }

  @Watch('selectedParticipantIds', { immediate: true })
  onSelectedParticipantIdsChanged(): void {
    this.participantIds = this.selectedParticipantIds;
  }

  updateSelectedParticipantIds(): void {
    this.$emit('update:selectedParticipantIds', this.participantIds);
  }

  getTranslationPath(axis: Axis): string {
    return `module.${axis.taskData.taskType.toLowerCase()}.${
      axis.taskData.moduleName
    }.analytics.highscore.`;
  }

  getIconOfAxis(axis: Axis): string | undefined {
    return axis.taskData.taskType
      ? getIconOfType(TaskType[axis.taskData.taskType.toUpperCase()])
      : undefined;
  }

  getColorOfAxis(axis: Axis): string | undefined {
    return axis.taskData.taskType
      ? getColorOfType(TaskType[axis.taskData.taskType.toUpperCase()])
      : undefined;
  }

  addToTableArray(axis: Axis): void {
    this.tableArray.push(axis);
  }

  removeFromTableArray(index: number): void {
    this.tableArray.splice(index, 1);
  }

  updateTableArray(index: number, axis: Axis): void {
    this.tableArray.splice(index, 1, axis);
  }

  filterParticipantData(taskId: string): HighScoreEntry[] {
    const chartData = this.chartData
      .filter((entry) => {
        const moduleAxis = entry.axes.find((a) => a.taskId === taskId);
        return moduleAxis?.axisValues.some((value) => value.value != null);
      })
      .map((entry) => ({
        ...entry,
        axes: entry.axes
          .filter((axis) => axis.taskId === taskId)
          .map((axis) => ({
            ...axis,
            axisValues: axis.axisValues.sort((a, b) => {
              const aIsLast = ['stars', 'rate'].includes(a.id);
              const bIsLast = ['stars', 'rate'].includes(b.id);
              return aIsLast === bIsLast ? 0 : aIsLast ? 1 : -1;
            }),
          })),
      }));

    return chartData.map((entry) => ({
      values: entry.axes[0].axisValues,
      participant: entry.participant,
    }));
  }
}
</script>

<style lang="scss" scoped>
.highscoreContainer {
  display: flex;
  justify-content: flex-start;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 3rem;
  width: 100%;
}

.highscoreContainer {
  display: flex;
  justify-content: flex-start;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 3rem;
  width: 100%;
}

.highScoreSelectionContainer {
  min-width: 700px;
  width: calc(50% - 1.5rem);
  overflow-y: scroll;
  scrollbar-width: none;
  -ms-overflow-style: none;
  overflow-x: hidden;
}

@media (max-width: calc((700px * 2) + 12rem)) {
  .highScoreSelectionContainer {
    width: 100%;
  }
}

.highscore {
  margin-top: 1rem;
}

.highScoreSelectionContainer::-webkit-scrollbar {
  display: none;
}

.el-dropdown-link {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.5rem;
  border-radius: var(--border-radius-xs);
  border: 2px solid transparent;
  padding: 0.2rem 0.6rem;
  transition: border 0.3s ease;
  cursor: pointer;
}

.el-dropdown-link:hover {
  border: 2px solid var(--color-background-darker);
}

.highscoreModuleSelection {
  display: flex;
  justify-content: space-between;
  align-items: center;

  .trashButton {
    background-color: transparent;
    padding: 0;
    margin: 0;
    font-size: var(--font-size-small);
    cursor: pointer;
  }
}

.highscoreModuleName {
  font-size: var(--font-size-default);
  font-weight: var(--font-weight-bold);
}

.highscoreModuleIcon {
  font-size: var(--font-size-xlarge);
}

.addOn__boarder {
  border: 2px dashed var(--color-primary);
  display: flex;
  justify-content: center;
  align-items: center;
}

.el-card {
  background-color: transparent;
}

.TableSelectionHeadline {
  font-size: var(--font-size-xlarge);
  font-weight: var(--font-weight-bold);
  margin-bottom: 0.5rem;
}
</style>
