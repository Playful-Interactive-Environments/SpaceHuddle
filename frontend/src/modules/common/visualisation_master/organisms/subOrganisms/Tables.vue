<template>
  <div class="highscoreContainer">
    <Highscore
      class="highscore"
      v-if="tasks[0] && chartData.length > 0"
      :module-id="tasks[0].modules[0].id"
      :participant-data="chartData"
      :selected-participant-id="selectedParticipantId"
      @participant-selected="participantSelectionChanged"
    />
    <Highscore
      class="highscore"
      v-if="tasks[0] && chartData.length > 0"
      :module-id="tasks[0].modules[0].id"
      :participant-data="chartData"
      :selected-participant-id="selectedParticipantId"
      @participant-selected="participantSelectionChanged"
    />
    <Highscore
      class="highscore"
      v-if="tasks[0] && chartData.length > 0"
      :module-id="tasks[0].modules[0].id"
      :participant-data="chartData"
      :selected-participant-id="selectedParticipantId"
      @participant-selected="participantSelectionChanged"
    />
  </div>
  <el-dropdown
    v-if="tasks.length > 1"
    v-on:command="taskInfo($event)"
    trigger="click"
    placement="bottom"
  >
    <div class="el-dropdown-link">dropdown</div>
    <template #dropdown>
      <el-dropdown-menu>
        <el-dropdown-item v-for="task in tasks" :key="task.id" :command="task">
          {{ task.name }}
        </el-dropdown-item>
      </el-dropdown-menu>
    </template>
  </el-dropdown>
  <el-button @click="console.log(chartData)">chartData</el-button>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Idea } from '@/types/api/Idea';

import Highscore from '@/modules/common/visualisation_master/organisms/subOrganisms/Highscore.vue';

import { ParticipantInfo } from '@/types/api/Participant';
import { Task } from '@/types/api/Task';

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
})
export default class Tables extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly tasks!: Task[];
  @Prop() readonly participantData!: DataEntry[];
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  openHighScoreLevels: string[] = [];
  ideas: Idea[] = [];
  sortColumn = 'normalisedTime';
  sortOrder = 1;

  selectedParticipantId = '';
  chartData: DataEntry[] = [];

  @Watch('participantData', { immediate: true })
  onChartDataChanged(): void {
    if (this.participantData != null) {
      this.chartData = this.participantData.filter((entry) => {
        for (const axis of entry.axes) {
          for (const value of axis.axisValues) {
            if (value.value != null) {
              return true;
            }
          }
        }
        return false;
      });
    }
  }

  taskInfo(task: Task[]) {
    console.log(task);
  }

  participantSelectionChanged(id: string) {
    this.selectedParticipantId = id;
  }
}
</script>

<style lang="scss" scoped>
.highscoreContainer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.highscore {
  width: 30%;
}
</style>
