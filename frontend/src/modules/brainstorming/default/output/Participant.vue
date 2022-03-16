<template>
  <ParticipantModuleDefaultContainer :task-id="taskId" :module="moduleName">
    <ValidationForm
      :form-data="formData"
      :use-default-submit="false"
      v-on:submitDataValid="save"
      v-on:reset="reset"
    >
      <el-form-item
        v-if="showSecondInput"
        prop="keywords"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.ruleToLong(MAX_KEYWORDS_LENGTH),
        ]"
      >
        <el-input
          v-model="formData.keywords"
          name="keywords"
          :placeholder="
            $t('module.brainstorming.default.participant.keywordInfo')
          "
        />
        <span
          class="info"
          :class="{
            error: MAX_KEYWORDS_LENGTH < formData.keywords.length,
          }"
        >
          {{
            $t('module.brainstorming.default.participant.remainingCharacters')
          }}:
          {{ MAX_KEYWORDS_LENGTH - formData.keywords.length }}
        </span>
      </el-form-item>
      <el-form-item
        prop="description"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.ruleToLong(MAX_DESCRIPTION_LENGTH),
        ]"
      >
        <el-input
          type="textarea"
          rows="4"
          v-model="formData.description"
          name="keywords"
          :placeholder="
            $t('module.brainstorming.default.participant.descriptionInfo')
          "
          v-on:keypress="submitOnEnter"
        />
        <span
          class="info"
          :class="{
            error: MAX_DESCRIPTION_LENGTH < formData.description.length,
          }"
        >
          {{
            $t('module.brainstorming.default.participant.remainingCharacters')
          }}:
          {{ MAX_DESCRIPTION_LENGTH - formData.description.length }}
        </span>
      </el-form-item>
      <el-form-item prop="imageWebLink" :rules="[defaultFormRules.ruleUrl]">
        <el-container>
          <el-aside width="8rem">
            <ImagePicker
              class="media-left"
              v-model:link="formData.imageWebLink"
              v-model:image="formData.imgDataUrl"
              :useEditOverlay="true"
            />
          </el-aside>
          <el-main>
            <!--<el-input
              v-if="showLinkInput"
              v-model="formData.imageWebLink"
              name="link"
              autocomplete="on"
              :placeholder="
                $t('module.brainstorming.default.participant.linkInfo')
              "
            />-->
          </el-main>
        </el-container>
      </el-form-item>
      <el-form-item
        prop="stateMessage"
        :rules="[defaultFormRules.ruleStateMessage]"
      >
        <span class="media">
          <!--<div class="media-left" role="button">
            <el-button v-on:click="showLinkInput = !showLinkInput" circle>
              <font-awesome-icon icon="link" />
            </el-button>
            <el-button v-on:click="showUploadDialog = !showUploadDialog" circle>
              <font-awesome-icon icon="paperclip" />
            </el-button>
            <el-button
              v-if="formData.imgDataUrl || formData.imageWebLink"
              v-on:click="deleteImage"
              circle
            >
              <font-awesome-icon icon="trash" />
            </el-button>
          </div>-->
          <div class="media-left">
            <el-button v-on:click="showHistory = true" type="primary">
              {{ $t('module.brainstorming.default.participant.ideas') }}:
              {{ ideas.length }}
            </el-button>
          </div>
          <span class="media-content">
            <el-input
              v-model="formData.stateMessage"
              class="hide"
              input-style="display: none"
            />
          </span>
          <div class="media-right" role="button">
            <el-button native-type="submit" circle>
              <font-awesome-icon icon="play" />
            </el-button>
          </div>
        </span>
      </el-form-item>

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
    </ValidationForm>
    <el-drawer
      v-model="showHistory"
      :title="$t('module.brainstorming.default.participant.ideas')"
      size="70%"
    >
      <el-divider></el-divider>
      <div class="layout__columns">
        <IdeaCard
          v-for="idea in ideas"
          :key="idea.id"
          :idea="idea"
          :is-selectable="false"
          :is-editable="true"
          :show-state="false"
          :canChangeState="false"
          :authHeaderTyp="EndpointAuthorisationType.PARTICIPANT"
          @ideaDeleted="getTaskIdeas"
        >
        </IdeaCard>
      </div>
    </el-drawer>
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/layout/ParticipantModuleDefaultContainer.vue';
import myUpload from 'vue-image-crop-upload/upload-3.vue';
import * as moduleService from '@/services/module-service';
import * as ideaService from '@/services/idea-service';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import { Module } from '@/types/api/Module';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { ElMessage } from 'element-plus';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { ValidationData } from '@/types/ui/ValidationRule';
import ImagePicker from '@/components/moderator/atoms/ImagePicker.vue';
import { submitOnEnter } from '@/types/ui/submit';
import {
  Idea,
  MAX_DESCRIPTION_LENGTH,
  MAX_KEYWORDS_LENGTH,
} from '@/types/api/Idea';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';

@Options({
  components: {
    IdeaCard,
    ParticipantModuleDefaultContainer,
    ValidationForm,
    ImagePicker,
    'my-upload': myUpload,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  submitOnEnter = submitOnEnter;

  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  module: Module | null = null;
  ideas: Idea[] = [];
  showHistory = false;

  MAX_KEYWORDS_LENGTH = MAX_KEYWORDS_LENGTH;
  MAX_DESCRIPTION_LENGTH = MAX_DESCRIPTION_LENGTH;
  EndpointAuthorisationType = EndpointAuthorisationType;

  get moduleName(): string {
    if (this.module) return this.module.name;
    return '';
  }

  formData: ValidationData = {
    keywords: '',
    description: '',
    imageWebLink: null,
    imgDataUrl: null, // the datebase64 url of created image
  };

  showUploadDialog = false;
  showLinkInput = false;

  planets = [
    require('@/assets/illustrations/planets/brainstorming01.png'),
    require('@/assets/illustrations/planets/brainstorming02.png'),
    require('@/assets/illustrations/planets/brainstorming03.png'),
    require('@/assets/illustrations/planets/brainstorming04.png'),
  ];
  scalePlanet = false;

  @Watch('moduleId', { immediate: true })
  onModuleIdChanged(): void {
    this.getModule();
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getTaskIdeas();
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

  get keywordsEmpty(): boolean {
    return this.showSecondInput && this.formData.keywords.length <= 0;
  }

  reset(): void {
    this.formData.keywords = '';
    this.formData.description = '';
    this.formData.imageWebLink = null;
    this.formData.imgDataUrl = null;
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  async save(): Promise<void> {
    ideaService
      .postIdea(this.taskId, {
        description:
          this.formData.keywords.length > 0 ? this.formData.description : '',
        keywords:
          this.formData.keywords.length > 0
            ? this.formData.keywords
            : this.formData.description,
        image: this.formData.imgDataUrl,
        link: this.formData.imageWebLink,
      })
      .then(
        (queryResult) => {
          this.reset();
          setTimeout(this.setNewPlanet, 500);
          ElMessage({
            message: (this as any).$i18n.translateWithFallback(
              'info.postIdea',
              [queryResult.keywords]
            ),
            type: 'success',
            center: true,
            showClose: true,
          });
          this.ideas.splice(0, 0, queryResult);
        },
        (error) => {
          this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
        }
      );
  }

  get showSecondInput(): boolean {
    return this.formData.description.length > MAX_KEYWORDS_LENGTH;
  }

  get activePlanetIndex(): number {
    if (this.ideas.length < this.planets.length) return this.ideas.length;
    return this.planets.length - 1;
  }

  setNewPlanet(): void {
    if (this.ideas.length >= this.planets.length) {
      this.scalePlanet = true;
      setTimeout(() => {
        this.scalePlanet = false;
      }, 1000);
    }
  }

  imageLinkChanged(): void {
    this.formData.imgDataUrl = null;
  }

  imageUploadSuccess(imgDataUrl: string): void {
    this.formData.imgDataUrl = imgDataUrl;
    this.formData.imageWebLink = null;
    (this.$refs.upload as any).setStep(1);
  }

  deleteImage(): void {
    this.formData.imgDataUrl = null;
    this.formData.imageWebLink = null;
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
        this.ideas = queryResult.filter((idea) => idea.isOwn).reverse();
      });
  }
}
</script>

<style lang="scss" scoped>
.el-button {
  border: unset;

  &.is-circle {
    padding: 0.4rem;

    &:focus,
    &:hover {
      background-color: unset;
    }
  }
}

.level-item {
  margin: 1.3rem auto;
  //margin-top: 0.5rem;
  //margin-bottom: 1rem;
}

.level.is-mobile .level-item:not(:last-child) {
  margin-bottom: auto;
}

.el-footer {
  text-align: right;
}

.layout__columns {
  padding: 1rem;
}

.el-badge::v-deep {
  .el-badge__content--primary {
    right: calc(1rem + var(--el-badge-size) / 2);
    top: calc(1rem + var(--el-badge-size) / 2);
    background-color: white;
    color: var(--color-mint-dark);
    font-weight: var(--font-weight-bold);
    font-size: 1rem;
  }
}

.el-divider--horizontal {
  background-color: var(--color-gray-inactive);
  margin: 0 1.5rem 1rem 1.5rem;
  width: calc(100% - 3rem);
}
</style>
