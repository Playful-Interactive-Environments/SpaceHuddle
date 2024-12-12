<template>
  <div class="highscoreContainer" v-if="axes && chartData.length > 0">
    <el-card
      class="highScoreSelectionContainer"
      v-for="(axis, index) in tableArray"
      :key="'highscoreSelectionContainer' + axis.moduleId"
      shadow="never"
      body-style="text-align: center"
      :class="{
        addOn__boarder: !(tableArray[index] && chartData.length > 0),
      }"
    >
      <div class="highscoreModuleSelection">
        <el-dropdown
          v-if="axes.length > 1"
          v-on:command="tableArray.splice(index, 1, $event)"
          trigger="click"
          placement="bottom"
        >
          <div class="el-dropdown-link">
            <font-awesome-icon
              class="highscoreModuleIcon"
              v-if="tableArray[index] && chartData.length > 0"
              :icon="getIconOfAxis(tableArray[index] || axis)"
              :style="{
                color: getColorOfAxis(tableArray[index] || axis),
              }"
            />
            <p
              class="oneLineText highscoreModuleName"
              v-if="tableArray[index] && chartData.length > 0"
            >
              {{ tableArray[index]?.taskData.taskName }}
              <font-awesome-icon :icon="['fas', 'angle-down']" />
            </p>
            <p class="oneLineText highscoreModuleName" v-else>
              select task
              <font-awesome-icon :icon="['fas', 'angle-down']" />
            </p>
          </div>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item
                v-for="(ax, axIndex) in axes"
                :key="ax ? ax.moduleId + 'ax' : axIndex"
                :command="ax ? ax : null"
              >
                {{ ax ? ax.taskData.taskName : 'N/A' }}
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
        <font-awesome-icon
          :icon="['fas', 'trash']"
          class="trashButton"
          v-if="tableArray[index] && chartData.length > 0"
          @click="removeFromTableArray(index)"
        />
      </div>
      <Highscore
        class="highscore"
        v-if="tableArray[index] && chartData.length > 0"
        :module-id="tableArray[index]!.moduleId"
        :table-data="filterParticipantData(JSON.parse(JSON.stringify(chartData)), tableArray[index]!.moduleId)"
        :selected-participant-id="selectedParticipantId"
        :translation-path="getTranslationPath(tableArray[index])"
        @participant-selected="participantSelectionChanged"
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
          v-if="axes.length > 1"
          v-on:command="addToTableArray($event)"
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
              <el-dropdown-item
                v-for="(ax, axIndex) in axes"
                :key="ax ? ax.moduleId + 'ax' : axIndex"
                :command="ax ? ax : null"
              >
                {{ ax ? ax.taskData.taskName : 'N/A' }}
              </el-dropdown-item>
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
import { Idea } from '@/types/api/Idea';

import Highscore from '@/components/moderator/organisms/analytics/subOrganisms/Highscore.vue';

import { ParticipantInfo } from '@/types/api/Participant';
import TaskType from '@/types/enum/TaskType';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import { HighScoreEntry } from '@/components/moderator/organisms/analytics/subOrganisms/Highscore.vue';

interface SubAxis {
  id: string;
  range: number;
}

interface Axis {
  moduleId: string;
  taskData: {
    taskType: TaskType;
    taskName: string;
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
}
interface DataEntry {
  participant: ParticipantInfo;
  axes: {
    moduleId: string;
    axisValues: AxisValue[];
  }[];
}

@Options({
  components: {
    Highscore,
  },
  emits: ['participantSelected'],
})
export default class Tables extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly axes!: Axis[];
  @Prop() readonly participantData!: DataEntry[];
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  ideas: Idea[] = [];
  sortOrder = 1;

  selectedParticipantId = '';
  chartData: DataEntry[] = [];
  tableCount = 0;
  tableArray: (Axis | null)[] = [];

  @Watch('participantData', { immediate: true })
  onChartDataChanged(): void {
    if (this.participantData != null) {
      this.chartData = this.participantData.filter((entry) =>
        entry.axes.some((axis) =>
          axis.axisValues.some((value) => value.value != null)
        )
      );
    }
  }

  getTranslationPath(axis: Axis | null): string {
    if (!axis) {
      return '';
    }
    return `module.${axis.taskData.taskType.toLowerCase()}.${
      axis.taskData.moduleName
    }.analytics.highscore.`;
  }

  participantSelectionChanged(id: string) {
    this.selectedParticipantId = id;
    this.$emit('participantSelected', id);
  }

  getIconOfAxis(axis: Axis): string | undefined {
    if (axis.taskData.taskType) {
      return getIconOfType(TaskType[axis.taskData.taskType.toUpperCase()]);
    }
  }

  getColorOfAxis(axis: Axis): string | undefined {
    if (axis.taskData.taskType) {
      return getColorOfType(TaskType[axis.taskData.taskType.toUpperCase()]);
    }
  }

  addToTableArray(axis: Axis): void {
    this.tableArray.push(axis);
    this.tableCount += 1;
  }

  removeFromTableArray(index: number): void {
    this.tableArray.splice(index, 1);
    this.tableCount -= 1;
  }

  filterParticipantData(participantData: DataEntry[], moduleId: string) {
    const chartData = participantData
      .filter((entry) => {
        const moduleAxis = entry.axes.find((a) => a.moduleId === moduleId);
        return moduleAxis?.axisValues.some((value) => value.value != null);
      })
      .map((entry) => ({
        ...entry,
        axes: entry.axes
          .filter((axis) => axis.moduleId === moduleId)
          .map((axis) => ({
            ...axis,
            axisValues: axis.axisValues.sort((a, b) => {
              const aIsLast = ['stars', 'rate'].includes(a.id);
              const bIsLast = ['stars', 'rate'].includes(b.id);
              return aIsLast === bIsLast ? 0 : aIsLast ? 1 : -1;
            }),
          })),
      }));
    const returnArray: HighScoreEntry[] = [];
    for (const entry of chartData) {
      returnArray.push({
        values: entry.axes[0].axisValues,
        participant: entry.participant,
      });
    }
    console.log(returnArray);
    return returnArray;
  }
}
</script>

<style lang="scss" scoped>
.highscoreContainer {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 3rem;
  width: 100%;
}

.highScoreSelectionContainer {
  width: calc(50% - 3rem);
  overflow-y: scroll;
  scrollbar-width: none;
  -ms-overflow-style: none;

  overflow-x: hidden;
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

.addOn {
  &__boarder {
    border: 2px dashed var(--color-primary);
    display: flex;
    justify-content: center;
    align-items: center;
  }
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
