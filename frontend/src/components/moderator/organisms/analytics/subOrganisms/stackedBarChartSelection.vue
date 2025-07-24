<template>
  <draggable
    v-if="hasData"
    v-model="surveyElementsData"
    v-bind="dragOptions"
    item-key="id"
    class="stackedChartsContainer"
    :group="{
      name: 'stackedChartsSelection',
      pull: 'true',
      put: false,
    }"
  >
    <template v-slot:item="{ element, index }">
      <el-card
        :id="element.taskData.taskId + 'surveyCard'"
        class="stackedChartsSelectionContainer"
        shadow="never"
        body-style="text-align: center"
        :class="{
          addOn__boarder: !element,
        }"
      >
        <div class="stackedChartsTaskSelection">
          <el-dropdown
            v-if="hasSurveyData"
            @command="(command) => updateSurveyElements(index, command)"
            trigger="click"
            placement="bottom"
          >
            <div class="el-dropdown-link">
              <font-awesome-icon
                v-if="element"
                class="highscoreModuleIcon"
                :icon="getIconOfType(TaskType.INFORMATION)"
                :style="{ color: getColorOfType(TaskType.INFORMATION) }"
              />
              <ToolTip>
                <template #content>
                  <div :style="{ textAlign: 'center' }">
                    <p class="heading heading--white">
                      {{
                        `${$t('moderator.molecule.moduleCount.topics')} ${
                          element.taskData.topicOrder + 1
                        }: ${element.taskData.topicName}`
                      }}
                    </p>
                    <p>{{ element.taskData.taskName }}</p>
                  </div>
                </template>
                <p class="oneLineText stackedChartsTaskName">
                  T{{ element.taskData.topicOrder + 1 }}:&nbsp;{{
                    element.taskData.taskName
                  }}
                </p>
              </ToolTip>
              <font-awesome-icon :icon="['fas', 'angle-down']" />
              <span class="participant-count stackedChartsTaskName"
                ><font-awesome-icon icon="user" />&nbsp;{{
                  getParticipantCount(element.questions)
                }}
              </span>
            </div>
            <template #dropdown>
              <el-dropdown-menu>
                <template
                  v-for="(sv, index) in surveyData"
                  :key="sv.taskData.taskId"
                >
                  <el-dropdown-item
                    v-if="isTopicHeading(index)"
                    class="heading oneLineText"
                    :divided="true"
                    :style="{ pointerEvents: 'none' }"
                    disabled
                  >
                    {{ sv.taskData.topicName }}
                  </el-dropdown-item>
                  <el-dropdown-item
                    :command="sv"
                    :divided="isTopicHeading(index)"
                  >
                    <font-awesome-icon
                      class="axisIcon"
                      :icon="getIconOfType(TaskType.INFORMATION)"
                      :style="{ color: getColorOfType(TaskType.INFORMATION) }"
                    />
                    <span>&nbsp;{{ sv.taskData.taskName }}</span>
                  </el-dropdown-item>
                </template>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
          <font-awesome-icon
            v-if="element"
            :icon="['fas', 'trash']"
            class="trashButton"
            @click="removeFromSurveyElements(index)"
          />
        </div>
        <stacked-bar-chart
          v-if="element"
          class="stackedChart"
          :task-id="element.taskData.taskId"
          :has-correct="isQuizOrTalk(element)"
          :chart-data="element.questions"
          :color-theme="colorTheme"
          :color-correct="'var(--color-brainstorming)'"
          :color-incorrect="'var(--color-evaluating)'"
          v-model:selectedParticipantIds="participantIds"
          @update:selected-participant-ids="updateSelectedParticipantIds"
        />
        <ToolTip
          :text="
            this.expandedElements.find(
              (elId) => elId === element.taskData.taskId + 'surveyCard'
            )
              ? $t('moderator.organism.analytics.collapse')
              : $t('moderator.organism.analytics.expand')
          "
          :placement="'bottom'"
        >
          <div
            class="expandCard"
            @click="handleExpandClick(element.taskData.taskId + 'surveyCard')"
          >
            <font-awesome-icon
              v-if="
                !this.expandedElements.find(
                  (elId) => elId === element.taskData.taskId + 'surveyCard'
                )
              "
              :icon="['fas', 'chevron-right']"
              class="expandIcon"
            />
            <font-awesome-icon
              v-else
              :icon="['fas', 'chevron-left']"
              class="expandIcon"
            />
          </div>
        </ToolTip>
      </el-card>
    </template>
  </draggable>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import StackedBarChart from '@/components/moderator/organisms/analytics/subOrganisms/stackedBarChart.vue';
import { Avatar } from '@/types/api/Participant';
import TaskType from '@/types/enum/TaskType';
import { getColorOfType, getIconOfType } from '@/types/enum/TaskCategory';
import ToolTip from '@/components/shared/atoms/ToolTip.vue';
import draggable from 'vuedraggable';

interface Answer {
  avatar: Avatar;
  answer: string[];
}

interface QuestionData {
  question: string;
  questionType: string;
  /* eslint-disable-next-line @typescript-eslint/no-explicit-any*/
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
  components: { ToolTip, StackedBarChart, draggable },
  emits: [
    'update:selectedParticipantIds',
    'update:surveyElements',
    'hasExpandedElement',
  ],
})
export default class StackedBarCharts extends Vue {
  @Prop() readonly surveyData!: SurveyData[];
  @Prop({ default: () => [] }) selectedParticipantIds!: string[];
  @Prop({ default: () => [] }) surveyElements!: SurveyData[];

  surveyElementsData: SurveyData[] = [];
  expandedElements: string[] = [];

  chartData: SurveyData[] = [];
  participantIds: string[] = [];
  colorTheme = [
    'var(--color-informing-light)',
    'var(--color-brainstorming-light)',
    'var(--color-structuring-light)',
    'var(--color-evaluating-light)',
    'var(--color-playing-light)',
  ];

  @Watch('surveyElements', { deep: true })
  onValueChanged(newValue: SurveyData[]) {
    this.surveyElementsData = [...newValue];
    this.$emit('update:surveyElements', newValue);
  }

  get dragOptions(): object {
    return {
      animation: 200,
      group: 'description',
      ghostClass: 'ghost',
    };
  }

  get hasData(): boolean {
    return !!this.surveyData && this.chartData.length > 0;
  }

  get hasSurveyData(): boolean {
    return this.surveyData.length >= 1;
  }

  hasExpandedElement(): boolean {
    return document.getElementsByClassName('expanded').length > 0;
  }

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

  updateHasExpandedElements(): void {
    this.$emit('hasExpandedElement', this.hasExpandedElement());
  }

  getColorOfType(taskType: TaskType) {
    return getColorOfType(taskType);
  }

  getIconOfType(taskType: TaskType) {
    return getIconOfType(taskType);
  }

  addToSurveyElements(survey: SurveyData): void {
    this.surveyElements.push(survey);
  }

  removeFromSurveyElements(index: number): void {
    this.surveyElements.splice(index, 1);
  }

  updateSurveyElements(index: number, survey: SurveyData): void {
    this.surveyElements.splice(index, 1, survey);
  }

  isQuizOrTalk(survey: SurveyData): boolean {
    return ['quiz', 'talk'].includes(survey.taskData.moduleName);
  }

  isTopicHeading(index: number): boolean {
    return (
      (this.surveyData[index - 1] &&
        this.surveyData[index].taskData.topicOrder !==
          this.surveyData[index - 1].taskData.topicOrder) ||
      index === 0
    );
  }

  getParticipantCount(questionDataArray: QuestionData[]): number {
    const uniqueAvatarIds = new Set<string>();

    questionDataArray.forEach((questionData) => {
      questionData.answers.forEach((answer) => {
        uniqueAvatarIds.add(answer.avatar.id);
      });
    });

    return uniqueAvatarIds.size;
  }

  handleExpandClick(id: string): void {
    const element = document.getElementById(id);
    if (element) {
      if (element.classList.contains('expanded')) {
        element.classList.remove('expanded');
        this.expandedElements = this.expandedElements.filter(
          (elId) => elId !== id
        );
      } else {
        element.classList.add('expanded');
        this.expandedElements.push(id);
      }
      this.updateHasExpandedElements();
    }
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
  cursor: move;
  min-width: 700px;
  width: calc(50% - 1.5rem);
  overflow: visible;
  position: relative;

  transition: width 0.4s ease;

  @media (max-width: calc((700px * 2) + 12rem)) {
    width: 100%;
  }

  @media print {
    width: 100%;
  }

  &::-webkit-scrollbar {
    display: none;
  }
}

.expanded {
  width: 100% !important;
}

.stackedChart {
  margin-top: 1rem;
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

  &:hover {
    border: 2px solid var(--color-background-darker);
  }
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

.participant-count {
  margin-left: 1rem;
}

.expandCard {
  position: absolute;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 1.2rem;
  height: 1.2rem;
  right: -1.2rem;
  top: 1rem;
  font-size: 0.7rem;
  transition: background-color 0.4s ease;
  color: var(--color-background);
  background-color: var(--color-background-darker);
  border-radius: 0 var(--border-radius-small) var(--border-radius-small) 0;
  cursor: pointer;
  .expandIcon {
    display: flex;
    align-items: center;
    justify-content: center;
  }
}
.expandCard:hover {
  background-color: var(--color-evaluating);
}

.ghost {
  opacity: 0.5;
  background: var(--color-background-dark);
}
</style>
