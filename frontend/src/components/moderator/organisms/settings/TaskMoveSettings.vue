<template>
  <ValidationForm
    :form-data="formData"
    :use-default-submit="false"
    v-on:submitDataValid="save"
    v-on:reset="reset"
  >
    <el-dialog v-model="showDialog" :before-close="handleClose">
      <template #header>
        <span class="el-dialog__title">
          {{ $t('moderator.organism.settings.taskMoveSettings.header') }}
        </span>
        <br />
        <br />
        <p>
          {{ $t('moderator.organism.settings.taskMoveSettings.info') }}
        </p>
      </template>

      <el-form-item
        v-if="!hasInput"
        prop="sessionId"
        :label="$t('moderator.organism.settings.taskMoveSettings.session')"
        :rules="[defaultFormRules.ruleRequired]"
      >
        <el-select v-model="formData.sessionId" class="select--fullwidth">
          <el-option
            v-for="session in sessions"
            :key="session.id"
            :value="session.id"
            :label="session.title"
          >
          </el-option>
        </el-select>
      </el-form-item>
      <el-form-item
        prop="topicId"
        :label="$t('moderator.organism.settings.taskMoveSettings.topic')"
        :rules="[defaultFormRules.ruleRequired]"
      >
        <el-select v-model="formData.topicId" class="select--fullwidth">
          <el-option
            v-for="topic in topics"
            :key="topic.id"
            :value="topic.id"
            :label="topic.title"
          >
          </el-option>
        </el-select>
      </el-form-item>
      <template #footer>
        <FromSubmitItem
          :form-state-message="formData.stateMessage"
          submit-label-key="moderator.organism.settings.taskMoveSettings.submit"
          :disabled="isSaving"
        />
      </template>
    </el-dialog>
  </ValidationForm>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as topicService from '@/services/topic-service';
import ValidationForm from '@/components/shared/molecules/ValidationForm.vue';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import { Topic } from '@/types/api/Topic';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';
import { Session } from '@/types/api/Session';
import * as sessionService from '@/services/session-service';
import EndpointType from '@/types/enum/EndpointType';

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
  },
  emits: ['update:showModal'],
})
export default class TaskMoveSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop({ default: false }) showModal!: boolean;
  @Prop({}) taskId!: string;
  task: Task | null = null;
  topics: Topic[] = [];
  sessions: Session[] = [];

  formData = {
    sessionId: '',
    topicId: '',
  };

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  get hasInput(): boolean {
    if (this.task && this.task.parameter.input) {
      return this.task.parameter.input.length > 0;
    }
    return false;
  }

  taskCash!: cashService.SimplifiedCashEntry<Task>;
  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    cashService.deregisterAllGet(this.updateTask);
    if (this.taskId) {
      this.taskCash = taskService.registerGetTaskById(
        this.taskId,
        this.updateTask,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    }
    sessionService.registerGetList(
      this.updateSessions,
      EndpointAuthorisationType.MODERATOR,
      2 * 60
    );
  }

  @Watch('formData.sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    cashService.deregisterAllGet(this.updateTopics);
    if (this.formData.sessionId) {
      if (this.formData.sessionId === this.task?.sessionId) {
        this.formData.topicId = this.task.topicId;
      } else this.formData.topicId = '';
      topicService.registerGetTopicsList(
        this.formData.sessionId,
        this.updateTopics,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    }
  }

  async updateTask(task: Task): Promise<void> {
    this.task = task;
    this.formData.sessionId = task.sessionId;
    this.formData.topicId = task.topicId;
  }

  async updateTopics(topics: Topic[]): Promise<void> {
    this.topics = topics;
    if (this.formData.topicId === '') {
      if (this.formData.sessionId === this.task?.sessionId) {
        this.formData.topicId = this.task.topicId;
      } else this.formData.topicId = topics[0].id;
    }
  }

  async updateSessions(sessions: Session[]): Promise<void> {
    this.sessions = sessions;
  }

  isSaving = false;
  async save(): Promise<void> {
    this.isSaving = true;
    await taskService.moveTask(this.taskId, this.formData.topicId);
    if (this.task) {
      cashService.refreshCash(
        `/${EndpointType.TOPIC}/${this.task?.topicId}/${EndpointType.TASKS}/`
      );
    }
    this.isSaving = false;
    this.reset();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    if (this.task) {
      this.formData.sessionId = this.task.sessionId;
      this.formData.topicId = this.task.topicId;
    }
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateTopics);
    cashService.deregisterAllGet(this.updateSessions);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped></style>
