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
          <span class="media-content"></span>
          <span class="media-right">
            <IdeaCard :idea="idea" :is-editable="false" class="public-idea" />
          </span>
        </div>
        <div class="media" v-if="newIdea.link || newIdea.image">
          <span class="media-content"></span>
          <img
            v-if="newIdea.link"
            class="media-right"
            :src="newIdea.link"
            alt="idea image"
          />
          <img
            v-if="newIdea.image"
            class="media-right"
            :src="newIdea.image"
            alt="idea image"
          />
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
        <el-form v-model="formData" @submit="saveIdea" class="media">
          <el-input
            class="media-content"
            v-model="formData.newIdea"
            :placeholder="
              $t('module.brainstorming.chat.participant.newIdeaInfo')
            "
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
        </el-form>
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

@Options({
  components: {
    IdeaCard,
    ParticipantModuleDefaultContainer,
    'my-upload': myUpload,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  module: Module | null = null;
  task: Task | null = null;
  readonly intervalTime = 10000;
  interval!: any;
  ideas: Idea[] = [];
  showUploadDialog = false;

  formData: any = {
    newIdea: '',
  };

  newIdea: Idea | any = {};

  saveIdea(event: Event): void {
    event.preventDefault();
    if (
      this.formData.newIdea.startsWith('http') ||
      this.formData.newIdea.startsWith('www')
    ) {
      if (!this.newIdea.image) this.newIdea.link = this.formData.newIdea;
    } else if (this.formData.newIdea.length > 60)
      this.newIdea.description = this.formData.newIdea;
    else this.newIdea.keywords = this.formData.newIdea;
    this.formData.newIdea = '';
    this.scrollToBottom(0);
    if (this.newIdea.keywords) {
      ideaService.postIdea(this.taskId, this.newIdea).then((done) => {
        if (done) {
          this.newIdea = {};
          this.getTaskIdeas();
        }
      });
    }
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
    await this.getTaskIdeas();
    //this.startIdeaInterval();
  }

  scrollToBottom(delay = 100): void {
    const element = document.getElementsByClassName('half-card')[0];
    setTimeout(() => {
      element.scrollTop = element.scrollHeight - element.clientHeight;
    }, delay);
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
        });
    }
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getTaskIdeas, this.intervalTime);
  }

  unmounted(): void {
    //clearInterval(this.interval);
  }

  async getTaskIdeas(): Promise<void> {
    ideaService
      .getIdeasForTask(
        this.taskId,
        IdeaSortOrder.TIMESTAMP,
        null,
        EndpointAuthorisationType.PARTICIPANT
      )
      .then((queryResult) => {
        this.ideas = queryResult;
        this.scrollToBottom();
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
    border-radius: 1rem 1rem 0 1rem;
    padding: 1rem;
  }

  img.media-right {
    //background-color: var(--color-mint-light);
    border: 3px solid var(--color-mint-light);
    border-radius: 1rem 1rem 0 1rem;
  }

  button.media-right {
    margin-left: 1rem;
    padding: 0.5rem 0.8rem;
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
</style>
