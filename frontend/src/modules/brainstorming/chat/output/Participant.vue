<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template #footer>
      <ValidationForm
        ref="inputForm"
        :form-data="formData"
        :use-default-submit="false"
        v-on:submitDataValid="saveIdea"
      >
        <el-form-item
          prop="newIdeaInput"
          :rules="[
            defaultFormRules.ruleRequired,
            defaultFormRules.ruleToLong(MAX_INPUT_LENGTH),
          ]"
        >
          <div class="media">
            <el-input
              class="media-content"
              v-model="formData.newIdeaInput"
              :placeholder="
                $t('module.brainstorming.chat.participant.newIdeaInfo')
              "
              type="textarea"
              :rows="1"
              :autosize="{ minRows: 1, maxRows: 10 }"
              v-on:blur="leaveField"
              v-on:focus="focusField"
              v-on:input="changeField"
            ></el-input>
            <el-button
              v-if="!newIdea.link && !inputHasFocus"
              class="media-right"
              type="primary"
              circle
              v-on:click="addImage"
            >
              <font-awesome-icon icon="paperclip"></font-awesome-icon>
            </el-button>
            <el-button
              v-if="!newIdea.link && !inputHasFocus"
              class="media-right"
              type="primary"
              circle
              v-on:click="addDrawing"
            >
              <font-awesome-icon icon="pencil"></font-awesome-icon>
            </el-button>
            <el-button
              class="media-right"
              type="primary"
              native-type="submit"
              circle
            >
              <font-awesome-icon icon="play"></font-awesome-icon>
            </el-button>
          </div>
          <span
            class="info"
            :class="{
              error: MAX_INPUT_LENGTH < formData.newIdeaInput.length,
            }"
          >
            {{
              $t(
                'module.brainstorming.default.participant.remainingCharacters'
              )
            }}:
            {{ MAX_INPUT_LENGTH - formData.newIdeaInput.length }}
          </span>
        </el-form-item>
      </ValidationForm>
    </template>
    <!--<div class="media" v-if="task">
      <span class="media-left">
        {{ task.name }}
      </span>
      <span class="media-content"></span>
    </div>-->
    <div class="media" v-for="idea in ideas" :key="idea.id">
      <span class="media-left" v-if="!idea.isOwn">
        <IdeaCard
          :idea="idea"
          :is-editable="false"
          class="public-idea"
          :show-state="false"
        />
      </span>
      <span class="media-content"></span>
      <span class="media-right" v-if="idea.isOwn">
        <IdeaCard
          :idea="idea"
          :is-editable="true"
          :show-state="false"
          :canChangeState="false"
          class="public-idea"
          :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
          @ideaDeleted="getTaskIdeas"
        />
      </span>
    </div>
    <div class="media" v-if="newIdea.link || newIdea.image">
      <span class="media-content"></span>
      <span class="media-right" v-if="newIdea.link">
        <img :src="newIdea.link" alt="idea image" />
        <br />
        <span v-on:click="newIdea.link = ''">
          <font-awesome-icon class="edit" icon="trash" />
        </span>
      </span>
      <span class="media-right" v-if="newIdea.image">
        <img :src="newIdea.image" alt="idea image" />
        <br />
        <span v-on:click="newIdea.image = ''">
          <font-awesome-icon class="edit" icon="trash" />
        </span>
      </span>
    </div>
    <div class="media" v-if="newIdea.link || newIdea.image">
      <span class="media-left media-left-error">
        {{ $t('module.brainstorming.chat.participant.addDescription') }}
      </span>
      <span class="media-content"></span>
    </div>
    <div class="media" v-if="newIdea.description">
      <span class="media-content"></span>
      <span class="media-right">
        {{ newIdea.description }}
        <span v-on:click="newIdea.description = ''">
          <font-awesome-icon class="edit" icon="trash" />
        </span>
      </span>
    </div>
    <div class="media" v-if="newIdea.description">
      <span class="media-left media-left-error">
        {{ $t('module.brainstorming.chat.participant.addKeywords') }}
      </span>
      <span class="media-content"></span>
    </div>

    <ImageUploader
      v-model:show-modal="showUploadDialog"
      v-model="base64ImageUrl"
    />
    <DrawingUpload
      v-model:show-modal="showDrawingDialog"
      v-model="base64ImageUrl"
    />
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import * as moduleService from '@/services/module-service';
import { Idea } from '@/types/api/Idea.ts';
import { Module } from '@/types/api/Module';
import { Task } from '@/types/api/Task';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import { MAX_DESCRIPTION_LENGTH, MAX_KEYWORDS_LENGTH } from '@/types/api/Idea';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import ImageUploader from '@/components/shared/organisms/ImageUploader.vue';
import DrawingUpload from '@/components/shared/organisms/DrawingUpload.vue';

@Options({
  components: {
    DrawingUpload,
    ValidationForm,
    IdeaCard,
    ParticipantModuleDefaultContainer,
    ImageUploader,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  module: Module | null = null;
  task: Task | null = null;
  readonly intervalTime = 10000;
  interval!: any;
  ideas: Idea[] = [];
  showUploadDialog = false;
  showDrawingDialog = false;
  base64ImageUrl: string | null = null;
  EndpointAuthorisationType = EndpointAuthorisationType;

  imageLoaded(): void {
    const scrollIsBottom = this.scrollIsBottom();
    if (scrollIsBottom) this.scrollToBottom();
  }

  get MAX_INPUT_LENGTH(): number {
    if (
      this.formData.newIdeaInput.startsWith('http') ||
      this.formData.newIdeaInput.startsWith('www')
    )
      return MAX_DESCRIPTION_LENGTH;
    if (this.newIdea.description) return MAX_KEYWORDS_LENGTH;
    return MAX_DESCRIPTION_LENGTH;
  }

  formData: any = {
    newIdeaInput: '',
  };

  newIdea: Idea | any = {};

  saveIdea(): void {
    if (
      this.formData.newIdeaInput.startsWith('http') ||
      this.formData.newIdeaInput.startsWith('www')
    ) {
      if (!this.newIdea.image) this.newIdea.link = this.formData.newIdeaInput;
    } else if (this.formData.newIdeaInput.length > MAX_KEYWORDS_LENGTH)
      this.newIdea.description = this.formData.newIdeaInput;
    else this.newIdea.keywords = this.formData.newIdeaInput;
    this.formData.newIdeaInput = '';
    this.scrollToBottom(0);
    if (this.newIdea.keywords) {
      ideaService.postIdea(this.taskId, this.newIdea).then((queryResult) => {
        if (queryResult) {
          this.newIdea = {};
          this.ideas.push(queryResult);
          const scrollIsBottom = this.scrollIsBottom();
          if (scrollIsBottom) this.scrollToBottom();
        }
      });
    }
  }

  inputHasFocus = false;
  leaveField(): void {
    this.inputHasFocus = false;
    if (!this.formData.newIdeaInput)
      this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  focusField(): void {
    this.inputHasFocus = true;
  }

  changeField(inputValue: string): void {
    if (this.$refs.inputForm && inputValue.includes('\n')) {
      (this.$refs.inputForm as any).submitData();
    }
  }

  addImage(): void {
    this.showUploadDialog = true;
  }

  addDrawing(): void {
    this.showDrawingDialog = true;
  }

  imageUploadSuccess(imgDataUrl: string): void {
    this.newIdea.image = imgDataUrl;
    this.scrollToBottom(0);
    (this.$refs.upload as any).setStep(1);
  }

  @Watch('base64ImageUrl', { immediate: true })
  onBase64ImageUrlChanged(): void {
    this.newIdea.image = this.base64ImageUrl;
    this.scrollToBottom(0);
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  async mounted(): Promise<void> {
    window.addEventListener('imageLoaded', this.imageLoaded, false);
    await this.getTaskIdeas(true);
    this.startInterval();
  }

  scrollToBottom(delay = 100): void {
    setTimeout(() => {
      window.scroll(0, document.body.scrollHeight);
    }, delay);
  }

  scrollIsBottom(): boolean {
    return window.scrollY === document.body.scrollHeight - window.innerHeight;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getTask();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService
        .getTaskById(this.taskId, EndpointAuthorisationType.PARTICIPANT)
        .then((task) => {
          this.task = task;
        });
    }
  }

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    this.getModule();
  }

  async getModule(): Promise<void> {
    if (this.moduleId) {
      await moduleService
        .getModuleById(this.moduleId, EndpointAuthorisationType.PARTICIPANT)
        .then((module) => {
          this.module = module;
          if (this.module && !this.module.parameter.showAll)
            this.ideas = this.ideas.filter((idea) => idea.isOwn);
        });
    }
  }

  startInterval(): void {
    this.interval = setInterval(this.getTaskIdeas, this.intervalTime);
  }

  unmounted(): void {
    window.removeEventListener('imageLoaded', this.imageLoaded);
    clearInterval(this.interval);
  }

  async getTaskIdeas(scrollToBottom = false): Promise<void> {
    const scrollIsBottom = this.scrollIsBottom();
    ideaService
      .getIdeasForTask(
        this.taskId,
        IdeaSortOrder.TIMESTAMP,
        null,
        EndpointAuthorisationType.PARTICIPANT
      )
      .then((queryResult) => {
        this.ideas = queryResult;
        if (this.module && !this.module.parameter.showAll)
          this.ideas = queryResult.filter((idea) => idea.isOwn);
        if (scrollToBottom || scrollIsBottom) this.scrollToBottom();
      });
  }
}
</script>

<style lang="scss" scoped>
.media {
  flex-direction: row;

  img {
    max-width: 15rem;
  }

  + .media {
    border-top: unset;
    padding-top: unset;
  }

  &-left,
  &-right {
    flex-shrink: unset;
    max-width: 22rem;
    overflow: hidden;
    //max-width: calc(var(--app-width) / 4);
    //border: 3px solid var(--color-mint-light);

    .el-card {
      border: unset;
      background-color: unset;
      margin: -0.8rem;
    }
  }

  &-content {
    min-width: calc(var(--app-width) * 0.1);
  }

  span.media-right {
    background-color: var(--color-mint-light);
    border: 3px solid var(--color-mint-light);
    border-radius: 1rem 1rem 0 1rem;
    padding: 0.5rem;

    img {
      margin: -0.5rem;
      margin-bottom: 0;
      overflow: hidden;
    }

    .fa-trash {
      padding-top: 0.2rem;
      padding-left: 0.2rem;
    }
  }

  img.media-right {
    //background-color: var(--color-mint-light);
    border: 3px solid var(--color-mint-light);
    border-radius: 1rem 1rem 0 1rem;
  }

  button.media-right {
    margin-left: 0.5rem;
    padding: 0;
    aspect-ratio: 1;
    flex-shrink: 0;
    height: 2rem;
  }

  &-left {
    background-color: var(--color-yellow-light);
    //border: 3px solid var(--color-yellow-light);
    border-radius: 1rem 1rem 1rem 0;
    padding: 1rem;

    &-error {
      background-color: var(--color-red-light);
    }
  }
}

.edit {
  float: right;
}

.el-form-item--default {
  margin-bottom: 0;
}
</style>
