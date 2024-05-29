<template>
  <div>
    <h1 class="heading heading--medium">
      {{ $t('module.information.brainhex.description.title') }}
    </h1>
    <div class="question" v-if="editQuestion">
      {{ editQuestion.question.order + 1 }}.
      {{
        $t(
          `module.information.brainhex.questions.${editQuestion.question.description
            .replaceAll('.', '')
            .replaceAll("'", '')}`
        )
      }}
    </div>
    <div
      v-if="
        editQuestion &&
        surveyConfig.questions[editQuestion.question.order].playerType
      "
    >
      {{
        $t(
          `module.information.brainhex.enum.playerType.${
            surveyConfig.questions[editQuestion.question.order].playerType
          }`
        )
      }}
    </div>

    <div id="QuizDiv">
      <QuizResult
        v-if="editQuestion"
        :voteResult="votes"
        :update="true"
        :questionType="editQuestion.questionType"
        :show-legend="true"
      />
      <QuizResult
        v-else-if="activeArea === TimelineArea.left"
        :voteResult="detailVotingResult"
        resultColumn="countParticipant"
        :change="false"
        :update="true"
      />
      <PlayerTypeResult v-else :task-id="taskId" :show-exception="true" />
    </div>

    <ProcessTimeline
      v-model="questions"
      v-model:publicScreen="publicQuestion"
      v-model:activeItem="editQuestion"
      :entityName="TimerEntity.TASK"
      :canDisablePublicTimeline="true"
      :isLinkedToDetails="true"
      keyPropertyName="id"
      :hasParticipantToggle="false"
      :hasParticipantOption="(item) => false"
      :canClickHome="true"
      :canDisableResult="true"
      :contentListIcon="(item) => null"
      :getKey="(item) => item.id"
      :getTitle="
        (item) => {
          if (surveyConfig.questions[item.order].playerType) {
            return $t(
              `module.information.brainhex.enum.playerType.${
                surveyConfig.questions[item.order].playerType
              }`
            );
          }
          return $t(`module.information.brainhex.moderatorContent.order`);
        }
      "
      :getTimerEntity="(item) => task"
      :itemIsEquals="
        (a, b) => (!a && !b) || (a && b && a.question.id === b.question.id)
      "
      :displayItem="(item) => item.question"
      :canDrag="false"
      accentColor="var(--color-informing)"
      @changeActiveElement="onEditQuestionChanged"
      @homeClicked="homeClicked"
      @resultClicked="resultClicked"
      @changePublicScreen="changePublicScreen"
    >
    </ProcessTimeline>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as taskService from '@/services/task-service';
import * as topicService from '@/services/topic-service';
import * as hierarchyService from '@/services/hierarchy-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import ProcessTimeline, {
  TimelineArea,
} from '@/components/moderator/organisms/Timeline/ProcessTimeline.vue';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import { Hierarchy } from '@/types/api/Hierarchy';
import { VoteResult } from '@/types/api/Vote';
import * as votingService from '@/services/voting-service';
import * as cashService from '@/services/cash-service';
import { TimerEntity } from '@/types/enum/TimerEntity';
import { convertToSaveVersion, Task } from '@/types/api/Task';
import {
  getQuestionResultStorageFromQuestionType,
  Question,
  QuestionResultStorage,
  QuestionType,
} from '@/modules/information/quiz/types/Question';
import QuizResult from '@/modules/information/brainhex/organisms/QuizResult.vue';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import ImagePicker from '@/components/moderator/atoms/ImagePicker.vue';
import { Topic } from '@/types/api/Topic';
import * as taskParticipantService from '@/services/task-participant-service';
import { TaskParticipantIterationStep } from '@/types/api/TaskParticipantIterationStep';
import Highscore from '@/modules/information/quiz/organisms/Highscore.vue';
import { until } from '@/utils/wait';
import MarkdownEditor from '@/components/shared/molecules/MarkdownEditor.vue';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import PlayerTypeResult from '@/modules/information/brainhex/organisms/PlayerTypeResult.vue';
import surveyConfig from '@/modules/information/brainhex/data/survey.json';

@Options({
  components: {
    PlayerTypeResult,
    MarkdownEditor,
    Highscore,
    ImagePicker,
    ValidationForm,
    ProcessTimeline,
    AddItem,
    IdeaSettings,
    IdeaCard,
    draggable,
    QuizResult,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop() readonly taskId!: string;
  task: Task | null = null;
  sessionId!: string;
  questions: Question[] = [];
  publicQuestion: Question | null = null;
  editQuestion: Question | null = null;
  ideas: Idea[] = [];

  QuestionType = QuestionType;
  activeArea: TimelineArea = TimelineArea.content;
  TimelineArea = TimelineArea;
  TimerEntity = TimerEntity;
  votes: VoteResult[] = [];
  surveyConfig = surveyConfig;

  questionCash!: cashService.SimplifiedCashEntry<Hierarchy[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    this.task = null;
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateQuestions);
    cashService.deregisterAllGet(this.updateFinalResult);
    taskService.registerGetTaskById(this.taskId, this.updateTask);
    this.questionCash = hierarchyService.registerGetQuestions(
      this.taskId,
      this.updateQuestions,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  async changePublicScreen(
    publicQuestion: Question | null,
    area: TimelineArea
  ): Promise<void> {
    if (this.task) {
      const task = this.task;
      const activeQuestion = publicQuestion?.question.id;
      task.parameter['activeQuestion'] = activeQuestion ? activeQuestion : null;
      task.parameter['activeArea'] = area;
      const updateData = { ...convertToSaveVersion(task) };
      await taskService.putTask(updateData);
    }
  }

  @Watch('editQuestion', { immediate: true })
  async onEditQuestionChanged(
    newValue: Question,
    oldValue: Question
  ): Promise<void> {
    if (!newValue) return;
    this.votes = [];
    const oldQuestionResultStorage = oldValue
      ? getQuestionResultStorageFromQuestionType(oldValue.questionType)
      : null;
    const newQuestionResultStorage = getQuestionResultStorageFromQuestionType(
      newValue.questionType
    );
    if (
      newQuestionResultStorage !== oldQuestionResultStorage ||
      newValue?.question?.id !== oldValue?.question?.id
    ) {
      cashService.deregisterAllGet(this.updateParentVotes);
      cashService.deregisterAllGet(this.updateVotes);
      cashService.deregisterAllGet(this.updateHierarchyResult);
      if (newValue?.question?.id) {
        if (newQuestionResultStorage === QuestionResultStorage.VOTING) {
          votingService.registerGetHierarchyResultDetail(
            newValue.question.id,
            this.updateVotes,
            EndpointAuthorisationType.MODERATOR,
            20
          );
        } else {
          hierarchyService.registerGetList(
            this.taskId,
            newValue.question.id,
            this.updateHierarchyResult,
            EndpointAuthorisationType.MODERATOR,
            20
          );
        }
      } else {
        votingService.registerGetParentResult(
          this.taskId,
          this.updateParentVotes,
          EndpointAuthorisationType.MODERATOR,
          20
        );
      }
    }
  }

  updateHierarchyResult(answers: Hierarchy[]): void {
    this.votes = hierarchyService.getHierarchyResult(
      answers,
      this.editQuestion?.question.parameter?.correctValue
    );
  }

  updateVotes(votes: VoteResult[]): void {
    this.votes = votes;
  }

  homeClicked(): void {
    this.activeArea = TimelineArea.left;
    this.editQuestion = null;
  }

  resultClicked(): void {
    this.activeArea = TimelineArea.right;
    this.editQuestion = null;
  }

  updateTask(task: Task): void {
    const init = !this.task;
    this.task = task;
    if (init) {
      cashService.deregisterAllGet(this.updateFinalResult);
      taskParticipantService.registerGetIterationStepFinalList(
        this.taskId,
        this.updateFinalResult,
        EndpointAuthorisationType.MODERATOR,
        20
      );
    }
    this.initPublicQuestion();
  }

  questionHierarchy: Hierarchy[] = [];
  updateQuestions(items: Hierarchy[]): void {
    this.questionHierarchy = items;
    const questions = hierarchyService.convertToQuestions(items);
    if (this.questions.length === 0) {
      this.questions = questions;
      this.initPublicQuestion();
      this.editQuestion = this.questions[0];
      questions.forEach(async (question) => {
        hierarchyService.registerGetList(
          this.taskId,
          question.question.id,
          this.updateAnswers,
          EndpointAuthorisationType.MODERATOR,
          60
        );
      });
    }
  }

  trackingResult: TaskParticipantIterationStep[] = [];
  updateFinalResult(result: TaskParticipantIterationStep[]): void {
    this.trackingResult = result;
  }

  updateParentVotes(votes: VoteResult[]): void {
    if (!this.editQuestion?.question?.id) {
      this.votes = hierarchyService.getParentResult(
        votes,
        this.questionHierarchy
      );
    }
  }

  get detailVotingResult(): VoteResult[] {
    if (this.trackingResult.length > 0 && this.questions.length > 0) {
      const result: VoteResult[] = [];
      for (const step of this.trackingResult) {
        const question = this.questions.find(
          (q) => q.question.id === step.ideaId
        );
        if (question) {
          const idea = { ...question.question };
          idea.parameter = { ...idea.parameter };
          idea.parameter.isCorrect =
            step.state === TaskParticipantIterationStepStatesType.CORRECT;
          const exists = result.find(
            (exist) =>
              exist.idea.id === idea.id &&
              exist.idea.parameter.isCorrect === idea.parameter.isCorrect
          );
          if (exists) exists.countParticipant++;
          else {
            result.push({
              idea: idea,
              ratingSum: 1,
              detailRatingSum: 1,
              countParticipant: 1,
              avatarList: [],
            });
          }
        }
      }
      return result;
    }
    return this.votes;
  }

  initPublicQuestion(): void {
    const activeQuestionId = this.task?.parameter?.activeQuestion;
    const publicQuestion: Question | undefined = this.questions.find(
      (question) => question.question.id === activeQuestionId
    );
    if (publicQuestion) this.publicQuestion = publicQuestion;
  }

  async updateAnswers(
    answers: Hierarchy[],
    questionId: string | null
  ): Promise<void> {
    if (questionId) {
      const question = this.questions.find(
        (question) => question.question.id === questionId
      );
      if (question) {
        const questionResultStorage: QuestionResultStorage =
          getQuestionResultStorageFromQuestionType(question.questionType);
        if (questionResultStorage === QuestionResultStorage.VOTING) {
          answers.forEach((item) => {
            if (!item.description) item.description = item.keywords;
          });
          await until(
            () =>
              answers.filter((item) => item.imageTimestamp && !item.image)
                .length === 0
          );
          question.answers = answers;
        } else {
          //
        }
      }
    }
  }

  @Watch('task.topicId', { immediate: true })
  onTopicIdChanged(newValue: string | null, oldValue: string | null): void {
    if (newValue)
      topicService.registerGetTopicById(
        newValue,
        this.updateTopic,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    if (oldValue)
      topicService.deregisterGetTopicById(oldValue, this.updateTopic);
  }

  updateTopic(topic: Topic): void {
    this.sessionId = topic.sessionId;
  }

  async reloadTaskSettings(): Promise<void> {
    //todo
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateTopic);
    cashService.deregisterAllGet(this.updateVotes);
    cashService.deregisterAllGet(this.updateAnswers);
    cashService.deregisterAllGet(this.updateHierarchyResult);
    cashService.deregisterAllGet(this.updateQuestions);
    cashService.deregisterAllGet(this.updateFinalResult);
    cashService.deregisterAllGet(this.updateParentVotes);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style scoped lang="scss">
.el-input {
  --el-input-background-color: white;
}

.select--fullwidth {
  width: 100%;
}

.icons {
  height: 100%;
  align-items: center;
  margin: auto;
  padding-left: 0.5rem;
}

#QuizDiv {
  position: relative;

  width: auto;
  height: auto;
}

.image-layout {
  display: flex;
  flex-direction: row;
  justify-content: space-between;

  &--form-data {
    width: 100%;
  }
  &--image {
    width: 12rem;
    margin-top: 2rem;
    margin-left: 1rem;
  }
}

.question {
  padding-top: 1rem;
  font-size: var(--font-size-xlarge);
}
</style>
