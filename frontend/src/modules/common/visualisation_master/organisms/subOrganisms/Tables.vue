<template>
  <div class="highscoreContainer" v-if="axes && chartData.length > 0">
    <div
      class="highScoreSelectionContainer"
      v-for="(axis, index) in axes.slice(0, tableCount)"
      :key="'highscoreSelectionContainer' + axis.moduleId"
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
              :icon="getIconOfAxis(tableArray[index] || axis)"
              :style="{
                color: getColorOfAxis(tableArray[index] || axis),
              }"
            />
            <p class="oneLineText highscoreModuleName">
              {{
                tableArray[index]
                  ? tableArray[index]?.taskData.taskName
                  : axes[0].taskData.taskName
              }}
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
      <Highscore
        class="highscore"
        v-if="tableArray[index] && chartData.length > 0"
        :module-id="tableArray[index]!.moduleId"
        :participant-data="JSON.parse(JSON.stringify(chartData))"
        :selected-participant-id="selectedParticipantId"
        @participant-selected="participantSelectionChanged"
      />
      <Highscore
        class="highscore"
        v-else-if="axes[0] && chartData.length > 0"
        :module-id="axes[0].moduleId"
        :participant-data="JSON.parse(JSON.stringify(chartData))"
        :selected-participant-id="selectedParticipantId"
        @participant-selected="participantSelectionChanged"
      />
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea } from '@/types/api/Idea';

import Highscore from '@/modules/common/visualisation_master/organisms/subOrganisms/Highscore.vue';

import { ParticipantInfo } from '@/types/api/Participant';
import TaskType from '@/types/enum/TaskType';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';

interface subAxis {
  id: string;
  range: number;
}

interface Axis {
  moduleId: string;
  taskData: {
    taskType: TaskType;
    taskName: string;
  };
  axisValues: (subAxis | null)[];
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

  tableCount = 2;
  tableArray: (Axis | null)[] = [null, null];

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

  participantSelectionChanged(id: string) {
    this.selectedParticipantId = id;
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
  height: 100%;
}

.highScoreSelectionContainer {
  width: calc(50% - 3rem);
  overflow-y: scroll;
  scrollbar-width: none;
  -ms-overflow-style: none;

  overflow-x: hidden;
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

.highscoreModuleName {
  font-size: var(--font-size-default);
  font-weight: var(--font-weight-bold);
}

.highscoreModuleIcon {
  font-size: var(--font-size-xlarge);
}
</style>
