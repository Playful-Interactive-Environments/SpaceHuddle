<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <template v-slot:planet>
      <img
        src="@/assets/illustrations/planets/brainstorming.png"
        alt="planet"
        class="module-container__planet"
      />
    </template>
    <el-container class="fill">
      <el-main>
        <div class="media" v-if="task">
          <span class="media-left">
            {{ task.name }}
          </span>
          <span class="media-content"></span>
        </div>
        <div class="media" v-for="idea in ideas" :key="idea.id">
          <span class="media-left" v-if="!idea.isOwn">
            <IdeaCard :idea="idea" :is-editable="false" class="public-idea" />
          </span>
          <span class="media-content"></span>
          <span class="media-right" v-if="idea.isOwn">
            <IdeaCard
              :idea="idea"
              :is-editable="true"
              :canChangeState="false"
              class="public-idea"
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
          <span class="media-left">
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
          <span class="media-left">
            {{ $t('module.brainstorming.chat.participant.addKeywords') }}
          </span>
          <span class="media-content"></span>
        </div>
      </el-main>
      <el-footer>
        <ValidationForm
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
                v-on:blur="leaveField"
              ></el-input>
              <el-button
                v-if="!newIdea.link"
                class="media-right"
                type="primary"
                circle
                v-on:click="addImage"
              >
                <font-awesome-icon icon="paperclip"></font-awesome-icon>
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
      </el-footer>
    </el-container>

    <my-upload
      id="upload"
      @crop-success="imageUploadSuccess"
      v-model="showUploadDialog"
      :width="512"
      :height="512"
      img-format="png"
      langType="en"
      :no-square="true"
      :no-circle="true"
      :no-rotate="false"
      :with-credentials="true"
      ref="upload"
    ></my-upload>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import * as moduleService from '@/services/module-service';
import { Idea } from '@/types/api/Idea.ts';
import { Module } from '@/types/api/Module';
import { Task } from '@/types/api/Task';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import myUpload from 'vue-image-crop-upload/upload-3.vue';
import { MAX_DESCRIPTION_LENGTH, MAX_KEYWORDS_LENGTH } from '@/types/api/Idea';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';

@Options({
  components: {
    ValidationForm,
    IdeaCard,
    ParticipantModuleDefaultContainer,
    'my-upload': myUpload,
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
      ideaService.postIdea(this.taskId, this.newIdea).then((done) => {
        if (done) {
          this.newIdea = {};
          this.getTaskIdeas(true);
        }
      });
    }
  }

  leaveField(): void {
    if (!this.formData.newIdeaInput)
      this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  addImage(): void {
    this.showUploadDialog = true;
  }

  imageUploadSuccess(imgDataUrl: string): void {
    this.newIdea.image = imgDataUrl;
    this.scrollToBottom(0);
    (this.$refs.upload as any).setStep(1);
  }

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  async mounted(): Promise<void> {
    await this.getTaskIdeas(true);
    this.startInterval();
  }

  scrollToBottom(delay = 100): void {
    const element = document.getElementsByClassName('half-card')[0];
    setTimeout(() => {
      element.scrollTop = element.scrollHeight - element.clientHeight;
    }, delay);
  }

  scrollIsBottom(): boolean {
    const element = document.getElementsByClassName('half-card')[0];
    return element.scrollTop === element.scrollHeight - element.clientHeight;
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
    //max-width: 25vw;
    //border: 3px solid var(--color-mint-light);

    .el-card {
      border: unset;
      background-color: unset;
      margin: -0.8rem;
    }
  }

  &-content {
    min-width: 10vw;
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
  }
}

.el-footer {
  margin-top: 1rem;
}

/*.el-main {
  overflow: auto;
}

.fill {
  max-height: 80vh;
}*/

.participant-container::v-deep {
  .half-card {
    padding: 1.5rem 2rem 0 2rem;
  }
}

.el-footer {
  position: sticky;
  bottom: 0; //-1.5rem;
  padding: 1rem 0;
  background-color: white;
}

.edit {
  float: right;
}
</style>
