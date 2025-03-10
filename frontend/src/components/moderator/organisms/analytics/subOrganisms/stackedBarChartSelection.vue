<template>
  <div class="stackedChartsContainer" v-if="surveyData && chartData.length > 0">
    <el-card
      class="stackedChartsSelectionContainer"
      v-for="(survey, index) in tableArray"
      :key="survey?.taskData.taskId"
      shadow="never"
      body-style="text-align: center"
      :class="{ addOn__boarder: !survey }"
    >
      <div class="stackedChartsTaskSelection">
        <el-dropdown
          v-if="surveyData.length >= 1"
          @command="updateTableArray(index, $event)"
          trigger="click"
          placement="bottom"
        >
          <div class="el-dropdown-link">
            <font-awesome-icon
              v-if="survey"
              class="highscoreModuleIcon"
              :icon="getIconOfType(TaskType.INFORMATION)"
              :style="{ color: getColorOfType(TaskType.INFORMATION) }"
            />
            <p class="oneLineText stackedChartsTaskName">
              {{ survey ? survey.taskData.taskName : 'select survey' }}
              <font-awesome-icon :icon="['fas', 'angle-down']" />
            </p>
          </div>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item
                v-for="sv in surveyData"
                :key="sv.taskData.taskId"
                :command="sv"
              >
                {{ sv.taskData.taskName }}
              </el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
        <font-awesome-icon
          :icon="['fas', 'trash']"
          class="trashButton"
          v-if="survey"
          @click="removeFromTableArray(index)"
        />
      </div>
      <stacked-bar-chart
        v-if="survey"
        class="stackedChart"
        :task-id="survey.taskData.taskId"
        :has-correct="
          survey.taskData.moduleName === 'quiz' ||
          survey.taskData.moduleName === 'talk'
        "
        :chart-data="survey.questions"
        :color-theme="colorTheme"
        :color-correct="'var(--color-brainstorming)'"
        :color-incorrect="'var(--color-evaluating)'"
        v-model:selectedParticipantIds="participantIds"
        @update:selected-participant-ids="updateSelectedParticipantIds"
      />
    </el-card>
    <el-card
      class="stackedChartsSelectionContainer addOn__boarder is-align-self-center"
      shadow="never"
      body-style="text-align: center"
    >
      <p class="stackedChartsSelectionHeadline">
        {{ $t('moderator.organism.analytics.stackedBarCharts.survey') }}
      </p>
      <div class="stackedChartsTaskSelection">
        <el-dropdown
          v-if="surveyData.length >= 1"
          @command="addToTableArray"
          trigger="click"
          placement="bottom"
        >
          <div class="el-dropdown-link">
            <p class="oneLineText stackedChartsTaskName">
              {{
                $t('moderator.organism.analytics.stackedBarCharts.selectTask')
              }}
              <font-awesome-icon :icon="['fas', 'angle-down']" />
            </p>
          </div>
          <template #dropdown>
            <el-dropdown-menu>
              <template
                v-for="(sv, index) in surveyData"
                :key="sv.taskData.taskId"
              >
                <el-dropdown-item
                  v-if="
                    (surveyData[index - 1] &&
                      sv.taskData.topicOrder !==
                        surveyData[index - 1].taskData.topicOrder) ||
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
                  {{ sv.taskData.topicName }}
                </el-dropdown-item>
                <el-dropdown-item
                  :command="sv"
                  :divided="
                    (surveyData[index - 1] &&
                      sv.taskData.topicOrder !==
                        surveyData[index - 1].taskData.topicOrder) ||
                    index === 0
                  "
                >
                  <font-awesome-icon
                    class="axisIcon"
                    :icon="getIconOfType(TaskType.INFORMATION)"
                    :style="{
                      color: getColorOfType(TaskType.INFORMATION),
                    }"
                  />
                  <span>&nbsp;{{ sv.taskData.taskName }}</span>
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
import StackedBarChart from '@/components/moderator/organisms/analytics/subOrganisms/stackedBarChart.vue';
import { Avatar } from '@/types/api/Participant';
import TaskType from '@/types/enum/TaskType';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';

interface Answer {
  avatar: Avatar;
  answer: string[];
}

interface QuestionData {
  question: string;
  questionType: string;
  parameter: any;
  answers: Answer[];
}

interface SurveyData {
  taskData: {
    moduleName: string;
    taskId: string;
    taskName: string;
    taskType: TaskType;
    topicName: string;
    topicOrder: number;
  };
  questions: QuestionData[];
}

@Options({
  computed: {
    TaskType() {
      return TaskType;
    },
  },
  components: {
    StackedBarChart,
  },
  emits: ['update:selectedParticipantIds'],
})
export default class StackedBarCharts extends Vue {
  @Prop() readonly surveyData!: SurveyData[];
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];

  chartData: SurveyData[] = [];
  tableArray: (SurveyData | null)[] = [];
  participantIds: string[] = [];
  colorTheme = [
    'var(--color-informing-light)',
    'var(--color-brainstorming-light)',
    'var(--color-structuring-light)',
    'var(--color-evaluating-light)',
    'var(--color-playing-light)',
  ];

  @Watch('surveyData', { immediate: true })
  onChartDataChanged(): void {
    if (this.surveyData) {
      this.chartData = this.surveyData;
    }
  }

  @Watch('selectedParticipantIds', { immediate: true })
  onSelectedParticipantIdsChanged(): void {
    this.participantIds = this.selectedParticipantIds;
  }

  updateSelectedParticipantIds(): void {
    this.$emit('update:selectedParticipantIds', this.participantIds);
  }

  getColorOfType(taskType: TaskType) {
    return getColorOfType(taskType);
  }

  getIconOfType(taskType: TaskType) {
    return getIconOfType(taskType);
  }

  addToTableArray(survey: SurveyData): void {
    this.tableArray.push(survey);
  }

  removeFromTableArray(index: number): void {
    this.tableArray.splice(index, 1);
  }

  updateTableArray(index: number, survey: SurveyData): void {
    this.tableArray.splice(index, 1, survey);
  }
}
</script>

<style lang="scss" scoped>
.stackedChartsContainer {
  position: relative;
  display: flex;
  justify-content: flex-start;
  align-items: flex-start;
  flex-wrap: wrap;
  gap: 3rem;
  width: 100%;
}

.stackedChartsSelectionContainer {
  min-width: 700px;
  width: calc(50% - 1.5rem);
  overflow-y: scroll;
  scrollbar-width: none;
  -ms-overflow-style: none;
  overflow-x: hidden;
}

@media (max-width: calc((700px * 2) + 12rem)) {
  .stackedChartsSelectionContainer {
    width: 100%;
  }
}

@media print {
  .stackedChartsSelectionContainer {
    width: 100%;
  }
}

.stackedChart {
  margin-top: 1rem;
}

.stackedChartsSelectionContainer::-webkit-scrollbar {
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

.stackedChartsTaskSelection {
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

.stackedChartsTaskName {
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

.stackedChartsSelectionHeadline {
  font-size: var(--font-size-xlarge);
  font-weight: var(--font-weight-bold);
  margin-bottom: 0.5rem;
}
</style>
