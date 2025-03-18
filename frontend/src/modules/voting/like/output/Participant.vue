<template>
  <ParticipantModuleDefaultContainer
    :task-id="taskId"
    :module="moduleName"
    :module-theme="theme"
  >
    <div id="preloader"></div>
    <div id="loadingScreen">
      <span>{{ $t('module.voting.vote.participant.waiting') }}...</span>
      <span
        id="loading"
        v-loading="true"
        element-loading-background="rgba(0, 0, 0, 0)"
      ></span>
    </div>
    <template #footer>
      <span class="previousNext">
        <el-button
          type="primary"
          class="el-button--submit"
          native-type="submit"
          :disabled="likedGroups.length <= 0"
          @click="submit"
          v-if="!submitScreen"
        >
          {{ $t('module.voting.vote.participant.submit') }}
        </el-button>
      </span>
    </template>
    <div v-if="!submitScreen" class="votingContainer">
      <p class="heading">
        Likes to give: {{ likesToGive - likesGiven }}
        <font-awesome-icon
          class="like-heart"
          :icon="['fas', 'heart']"
          :style="{
            color: 'var(--color-evaluating)',
          }"
        />
      </p>
      <div
        v-for="ideaGroup in ideasByParticipant"
        :key="ideaGroup.groupId"
        class="GalleryContainer"
      >
        <Gallery
          :ideas="ideaGroup.ideas"
          :portrait="true"
          class="gallery"
          :time-modifier="1"
          :type="''"
          :indicator-position="'none'"
          :item-max-width="'100%'"
          :arrow="'hover'"
        />
        <p class="like-count">
          <font-awesome-icon
            v-if="likedGroups.includes(ideaGroup.groupId)"
            class="like-heart"
            :icon="['fas', 'heart']"
            @click="toggleSelection(ideaGroup.groupId)"
            :style="{
              color: 'var(--color-evaluating)',
            }"
          />
          <font-awesome-icon
            v-else
            class="like-heart"
            :icon="['far', 'heart']"
            @click="toggleSelection(ideaGroup.groupId)"
            :style="{
              color: 'var(--color-dark-contrast)',
            }"
          />
        </p>
      </div>
    </div>
    <div id="submitScreen" v-if="submitScreen">
      <span>{{ $t('module.voting.vote.participant.thanksIndividual') }}</span>
      <br />
      <el-button
        type="primary"
        class="el-button--submit"
        native-type="submit"
        @click="$router.back()"
      >
        {{ $t('module.voting.vote.participant.back') }}
      </el-button>
    </div>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as votingService from '@/services/voting-service';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import { QuestionnaireType } from '@/modules/information/quiz/types/QuestionnaireType';
import { QuestionType } from '@/modules/information/quiz/types/Question';
import { Hierarchy } from '@/types/api/Hierarchy';
import * as cashService from '@/services/cash-service';
import TaskParticipantStatesType from '@/types/enum/TaskParticipantStatesType';
import TaskParticipantIterationStepStatesType from '@/types/enum/TaskParticipantIterationStepStatesType';
import TaskParticipantIterationStatesType from '@/types/enum/TaskParticipantIterationStatesType';
import { TrackingManager } from '@/types/tracking/TrackingManager';
import { Idea } from '@/types/api/Idea';
import * as viewService from '@/services/view-service';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { ElMessageBox } from 'element-plus';
import EndpointType from '@/types/enum/EndpointType';
import Gallery from '@/modules/common/visualisation_master/organisms/gallery.vue';

interface IdeaGroup {
  groupId: string;
  ideas: Idea[];
}

@Options({
  components: {
    Gallery,
    IdeaCard,
    ParticipantModuleDefaultContainer,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  activeQuestion: Hierarchy | null = null;
  module: Module | null = null;
  task: Task | null = null;
  EndpointAuthorisationType = EndpointAuthorisationType;
  questionnaireType: QuestionnaireType = QuestionnaireType.QUIZ;
  moderatedQuestionFlow = true;
  score = 0;
  theme = '';
  answerId = '';

  ideas: Idea[] = [];
  allIdeas: Idea[] = [];
  groupedIdeas: IdeaGroup[] = [];

  likesGiven = 0;

  likedGroups: string[] = [];

  trackingManager!: TrackingManager;
  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;

  QuestionType = QuestionType;
  submitScreen = false;

  ideasByParticipant: IdeaGroup[] = [];

  get isMobile(): boolean {
    return window.innerWidth < 600;
  }

  get likesToGive(): number {
    return Math.floor(this.ideasByParticipant.length / 1.5);
  }

  hasAnswer(): boolean {
    return this.hasQuestionAnCorrectAnswer(this.activeQuestion);
  }

  hasQuestionAnCorrectAnswer(question: Hierarchy | null): boolean {
    if (question && Object.hasOwn(question.parameter, 'hasAnswer'))
      return question.parameter.hasAnswer;
    return this.questionnaireType !== QuestionnaireType.SURVEY;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    if (this.taskId) {
      this.trackingManager = new TrackingManager(this.taskId, {}, 100);
    }
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      60 * 60
    );
    this.inputCash = viewService.registerGetInputIdeas(
      this.taskId,
      IdeaSortOrder.PARTICIPANT,
      null,
      this.updateInputIdeas,
      EndpointAuthorisationType.PARTICIPANT,
      10
    );
  }

  inputIdeas: Idea[] = [];
  updateInputIdeas(ideas: Idea[]): void {
    this.inputIdeas = ideas;
    this.updateIdeas();
  }

  updateIdeas(): void {
    const ideas = viewService.customizeView(
      this.inputIdeas,
      null,
      (this as any).$t,
      [],
      '',
      this.task ? this.task.parameter.input.length : 1
    );
    this.allIdeas = [...ideas];
    this.ideas = ideas;
    this.ideasByParticipant = [];
    this.groupIdeasByParticipant(this.ideas);
  }

  groupIdeasByParticipant(ideas: Idea[]): void {
    if (this.task) {
      for (const ideaGroup of this.task.modules[0].parameter.ideaGroups) {
        this.ideasByParticipant.push({
          groupId: ideaGroup.groupId,
          ideas: ideas
            .filter((idea) => ideaGroup.ideaIds.includes(idea.id))
            .sort(),
        });
      }
    }
  }

  toggleSelection(id: string): void {
    if (this.likedGroups.includes(id)) {
      this.likedGroups.splice(
        this.likedGroups.findIndex((element) => element === id),
        1
      );
      this.likesGiven -= 1;
    } else {
      if (this.likesToGive - this.likesGiven > 0) {
        this.likedGroups.push(id);
        this.likesGiven += 1;
      }
    }
  }

  get loading(): boolean {
    const element = document.getElementById('loadingScreen');

    if (element && !element.classList.contains('zeroOpacity')) {
      const preload = document.getElementById('preloader');
      preload?.classList.add('PreloadSprites');

      setTimeout(() => preload?.classList.remove('PreloadSprites'), 1000);
      setTimeout(() => element?.classList.add('zeroOpacity'), 1000);
      setTimeout(() => element?.classList.add('hidden'), 3000);
      return true;
    }
    return false;
  }

  initData = true;
  mounted(): void {
    this.initData = true;
    this.loading;
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    if (this.moduleId) {
      moduleService.registerGetModuleById(
        this.moduleId,
        this.updateModule,
        EndpointAuthorisationType.PARTICIPANT,
        60 * 60
      );
    }
  }

  updateModule(module: Module): void {
    this.module = module;
    if (module.parameter.theme) this.theme = module.parameter.theme;
    this.moderatedQuestionFlow = module.parameter.moderatedQuestionFlow;
    if (this.moderatedQuestionFlow) this.initData = false;
  }

  updateTask(task: Task): void {
    this.task = task;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
    cashService.deregisterAllGet(this.updateTask);
  }

  async unmounted(): Promise<void> {
    if (!this.submitScreen && !!this.answerId) {
      let submit = false;
      await ElMessageBox.confirm(
        this.$t('module.voting.vote.participant.saveQuestion'),
        'Warning',
        {
          confirmButtonText: this.$t('module.voting.vote.participant.yes'),
          cancelButtonText: this.$t('module.voting.vote.participant.no'),
          type: 'warning',
        }
      )
        .then(() => {
          submit = true;
        })
        .catch(() => {
          submit = false;
        });
      if (submit) await this.submit();
    }
    this.deregisterAll();
    if (this.trackingManager) this.trackingManager.deregisterAll();
  }

  async submit() {
    this.submitScreen = true;

    for (const group of this.ideasByParticipant) {
      if (this.likedGroups.includes(group.groupId)) {
        for (const idea of group.ideas) {
          await this.postVote(idea.id, 1);
        }
      } else {
        for (const idea of group.ideas) {
          await this.postVote(idea.id, 0);
        }
      }
    }
  }

  async postVote(ideaId: string, rating: number) {
    const vote = await votingService.postVote(this.taskId, {
      ideaId: ideaId,
      rating: 1,
      detailRating: 1,
    });
    if (this.trackingManager) {
      await this.trackingManager.createInstanceStepPoints(
        vote.ideaId,
        TaskParticipantIterationStepStatesType.NEUTRAL,
        {
          rating: vote.rating,
          detailRating: vote.detailRating,
          parameter: vote.parameter,
        },
        100,
        null,
        true,
        false,
        () => true
      );
      await this.trackingManager.saveIteration(
        null,
        TaskParticipantIterationStatesType.PARTICIPATED,
        null,
        true
      );
      await this.trackingManager.saveState(
        {
          voteCount: 1,
        },
        TaskParticipantStatesType.FINISHED
      );
    }
    if (this.task)
      cashService.refreshCash(
        `/${EndpointType.SESSION}/${this.task.sessionId}/${EndpointType.PARTICIPANT_STATE}`
      );
  }
}
</script>

<style lang="scss" scoped>
.GalleryContainer {
  position: relative;
  width: 100%;
  height: 30rem;
  .gallery {
    width: 100%;
    height: 100%;
  }
  .like-count {
    position: absolute;
    bottom: 1rem;
    right: 1rem;
    text-align: right;
    font-size: var(--font-size-xlarge);
    .like-heart {
      animation: grow 0.3s ease forwards;
    }
  }
}

@keyframes grow {
  0% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.15);
  }
  100% {
    transform: scale(1);
  }
}

.el-footer {
  height: auto;
}

.explanation {
  width: 100%;
  text-align: justify;
  white-space: pre-line;
}

.previousNext {
  width: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

.el-button {
  padding: 1rem 2rem;
  justify-content: left;
}

div#loadingScreen {
  position: absolute;
  width: 100%;
  height: 100%;

  max-width: 760px;

  bottom: 0;
  left: 0;
  right: 0;
  margin-left: auto;
  margin-right: auto;

  background-color: var(--color-dark-contrast);

  display: flex;
  justify-items: center;
  align-items: center;
  flex-direction: column;

  opacity: 1;
  z-index: 2;
}

div#loadingScreen > span {
  width: 70%;
  text-align: center;
  color: white;
  font-size: var(--font-size-large);
  position: relative;
  margin: auto auto 0;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
}

div#loadingScreen > span#loading {
  margin-top: 50px;
  margin-bottom: auto;
}

div#loadingScreen > span#loading::v-deep(.path) {
  stroke: white;
  stroke-width: 4;
}

.zeroOpacity {
  opacity: 0 !important;
  transition: 2s;
}

.hidden {
  display: none !important;
}
</style>
