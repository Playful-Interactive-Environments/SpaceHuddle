<template>
  <ParticipantModuleDefaultContainer :task-id="taskId">
    <template v-slot:planet>
      <transition name="fade" v-for="(planet, index) in planets" :key="index">
        <img
          v-if="activePlanetIndex === index"
          :src="planets[index]"
          alt="planet"
          class="brainstorming__planet"
        />
      </transition>
    </template>
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
          defaultFormRules.ruleToLong(36),
        ]"
      >
        <el-input
          v-model="formData.keywords"
          name="keywords"
          :placeholder="
            $t('module.brainstorming.default.participant.keywordInfo')
          "
        />
      </el-form-item>
      <el-form-item
        prop="description"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.ruleToLong(255),
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
      </el-form-item>
      <el-form-item prop="imageWebLink" :rules="[defaultFormRules.ruleUrl]">
        <el-container>
          <el-aside width="7rem">
            <ImagePicker
              class="media-left"
              v-model:link="formData.imageWebLink"
              v-model:image="formData.imgDataUrl"
              :overlay="false"
            />
          </el-aside>
          <el-container>
            <el-main>
              <el-input
                v-if="showLinkInput"
                v-model="formData.imageWebLink"
                name="link"
                autocomplete="on"
                :placeholder="
                  $t('module.brainstorming.default.participant.linkInfo')
                "
              />
            </el-main>
            <el-footer>
              <div role="button">
                <el-button v-on:click="showLinkInput = !showLinkInput" circle>
                  <font-awesome-icon icon="share-alt" />
                </el-button>
                <el-button
                  v-on:click="showUploadDialog = !showUploadDialog"
                  circle
                >
                  <font-awesome-icon icon="paperclip" />
                </el-button>
                <el-button native-type="submit" circle>
                  <font-awesome-icon icon="paper-plane" />
                </el-button>
              </div>
            </el-footer>
          </el-container>
        </el-container>
      </el-form-item>
      <el-form-item
        prop="stateMessage"
        :rules="[defaultFormRules.ruleStateMessage]"
      >
        <el-input v-model="formData.stateMessage" style="display: none" />
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
  </ParticipantModuleDefaultContainer>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import ParticipantModuleDefaultContainer from '@/components/participant/organisms/ParticipantModuleDefaultContainer.vue';
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

@Options({
  components: {
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

  formData: ValidationData = {
    keywords: '',
    description: '',
    imageWebLink: null,
    imgDataUrl: null, // the datebase64 url of created image
  };

  showUploadDialog = false;
  showLinkInput = false;

  activePlanetIndex = 0;
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
          });
        },
        (error) => {
          this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
        }
      );
  }

  get showSecondInput(): boolean {
    return this.formData.description.length > 60;
  }

  setNewPlanet(): void {
    if (this.activePlanetIndex < this.planets.length - 1) {
      this.activePlanetIndex++;
    } else {
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
}
</script>

<style lang="scss" scoped>
.el-button {
  border: unset;

  &:focus {
    background-color: unset;
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

.el-container.is-vertical {
  padding-left: 1rem;
}

.el-footer {
  text-align: right;
}
</style>
