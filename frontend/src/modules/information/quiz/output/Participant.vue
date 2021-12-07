<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template v-slot:planet>
      <img
        src="@/assets/illustrations/planets/information.png"
        alt="planet"
        class="module-container__planet"
      />
    </template>
    <PublicBase
      :taskId="taskId"
      v-on:changeQuizState="getTask"
      v-on:changePublicQuestion="(id) => activeQuestionId = id"
      v-on:changePublicAnswers="(answers) => publicAnswerList = answers"
    >
      <template #answers>
        <el-space direction="vertical" class="fill">
          <el-button
            v-for="answer in publicAnswerList"
            type="primary"
            :key="answer.answer.id"
            class="link"
            :plain="!answer.isHighlighted"
            :disabled="!isActive"
            :loading="isSaving(answer.answer.id)"
            v-on:click="changeVote(answer.answer.id)"
          >
            <font-awesome-icon
              v-if="isAnswerSelected(answer.answer.id)"
              icon="check"
            />
            {{ answer.answer.keywords }}
          </el-button>
        </el-space>
      </template>
    </PublicBase>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as votingService from '@/services/voting-service';
import { Vote } from '@/types/api/Vote';
import PublicBase, {
  PublicAnswerData,
} from '@/modules/information/quiz/organisms/PublicBase.vue';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import * as timerService from '@/services/timer-service';

@Options({
  components: {
    ParticipantModuleDefaultContainer,
    PublicBase,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  publicAnswerList: PublicAnswerData[] = [];
  activeQuestionId = '';
  module: Module | null = null;
  task: Task | null = null;
  votes: Vote[] = [];

  get isActive(): boolean {
    if (this.task) return timerService.isActive(this.task);
    return false;
  }

  isAnswerSelected(answerId: string): boolean {
    return !!this.votes.find((vote) => vote.ideaId == answerId);
  }

  isSavingList: string[] = [];
  isSaving(answerId: string): boolean {
    return this.isSavingList.includes(answerId);
  }

  async changeVote(answerId: string): Promise<void> {
    if (!this.isSaving(answerId)) {
      this.isSavingList.push(answerId);
      const vote = this.votes.find((vote) => vote.ideaId == answerId);
      if (vote) {
        await votingService.deleteVote(vote.id).then((result) => {
          if (result) {
            const index = this.votes.findIndex(
              (vote) => vote.ideaId == answerId
            );
            this.votes.splice(index, 1);
          }
        });
      } else {
        await votingService
          .postVote(this.taskId, {
            ideaId: answerId,
            rating: 1,
            detailRating: 1,
          })
          .then((vote) => {
            this.votes.push(vote);
          });
      }
      const index = this.isSavingList.indexOf(answerId);
      this.isSavingList.splice(index, 1);
    }
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    this.getModule();
  }

  @Watch('activeQuestionId', { immediate: true })
  onActiveQuestionIdChanged(): void {
    this.getTask();
    this.getVotes();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getTask();
  }

  async getTask(): Promise<void> {
    await taskService.getTaskById(this.taskId).then((task) => {
      this.task = task;
    });
  }

  async getModule(): Promise<void> {
    if (this.moduleId) {
      await moduleService
        .getModuleById(this.moduleId, EndpointAuthorisationType.PARTICIPANT)
        .then((module) => {
          this.module = module;
        });
    }
  }

  async getVotes(): Promise<void> {
    if (this.activeQuestionId) {
      await votingService
        .getHierarchyVotes(this.activeQuestionId)
        .then((votes) => {
          this.votes = votes;
        });
    }
  }
}
</script>

<style lang="scss" scoped>
.el-space::v-deep {
  .el-space__item {
    width: 100%;

    button {
      width: 100%;
    }
  }
}

.question {
  border: 1px solid var(--color-primary);
  border-radius: var(--border-radius);
  padding: 1rem;
  font-weight: var(--font-weight-semibold);
  text-transform: uppercase;
  text-align: center;
  color: var(--color-primary);
  margin: 1em 0;
}

.explanation {
  width: 100%;
  text-align: justify;
  white-space: pre-line;
}
</style>
