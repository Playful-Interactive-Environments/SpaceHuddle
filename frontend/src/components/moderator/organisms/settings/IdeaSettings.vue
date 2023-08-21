<template>
  <ValidationForm
    :form-data="formData"
    :use-default-submit="false"
    v-on:submitDataValid="save"
    v-on:reset="reset"
    ref="dataForm"
  >
    <el-dialog
      v-model="showSettings"
      width="calc(var(--app-width) * 0.8)"
      :before-close="handleClose"
    >
      <template #header>
        <span class="el-dialog__title" v-if="title">{{ title }}</span>
        <span class="el-dialog__title" v-else>{{
          $t('moderator.organism.settings.ideaSettings.header')
        }}</span>
      </template>
      <el-form-item
        :label="$t('moderator.organism.settings.ideaSettings.keywords')"
        prop="keywords"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.ruleToLong(MAX_KEYWORDS_LENGTH),
        ]"
      >
        <el-input
          v-model="formData.keywords"
          :placeholder="
            $t('moderator.organism.settings.ideaSettings.keywordsExample')
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
        :label="$t('moderator.organism.settings.ideaSettings.description')"
        prop="description"
        :rules="[defaultFormRules.ruleToLong(MAX_DESCRIPTION_LENGTH)]"
      >
        <el-input
          type="textarea"
          v-model="formData.description"
          rows="4"
          :placeholder="
            $t('moderator.organism.settings.ideaSettings.descriptionExample')
          "
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
      <el-form-item
        :label="$t('moderator.organism.settings.ideaSettings.image')"
        prop="image"
      >
        <ImagePicker
          v-model:link="formData.link"
          v-model:image="formData.image"
        />
      </el-form-item>
      <slot></slot>
      <template #footer>
        <FromSubmitItem
          :form-state-message="formData.stateMessage"
          submit-label-key="moderator.organism.settings.ideaSettings.submit"
          :disabled="isSaving"
        />
      </template>
    </el-dialog>
  </ValidationForm>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as ideaService from '@/services/idea-service';
import { Idea } from '@/types/api/Idea';
import ImagePicker from '@/components/moderator/atoms/ImagePicker.vue';
import myUpload from 'vue-image-crop-upload/upload-3.vue';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { ValidationData } from '@/types/ui/ValidationRule';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import { MAX_DESCRIPTION_LENGTH, MAX_KEYWORDS_LENGTH } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
    ImagePicker,
    'my-upload': myUpload,
  },
  emits: ['update:showModal', 'updateData'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: null }) title!: string | null;
  @Prop({ default: null }) taskId!: string | null;
  @Prop() idea!: Idea;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;

  MAX_KEYWORDS_LENGTH = MAX_KEYWORDS_LENGTH;
  MAX_DESCRIPTION_LENGTH = MAX_DESCRIPTION_LENGTH;

  formData: ValidationData = {
    keywords: '',
    description: '',
    image: null,
    link: null,
  };

  showSettings = false;

  mounted(): void {
    this.reset();
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.formData.keywords = this.idea.keywords;
    this.formData.description = this.idea.description ?? '';
    this.formData.image = this.idea.image;
    this.formData.link = this.idea.link;
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
    this.reset();
  }

  @Watch('idea', { immediate: true, deep: true })
  async onIdeaChanged(idea: Idea): Promise<void> {
    if (!this.showSettings) {
      this.formData.keywords = idea.keywords;
      this.formData.description = idea.description ?? '';
      this.formData.image = idea.image;
      this.formData.link = idea.link;
    }
  }

  isSaving = false;
  async save(): Promise<void> {
    this.isSaving = true;
    this.idea.keywords = this.formData.keywords;
    this.idea.description = this.formData.description;
    this.idea.image = this.formData.image;
    this.idea.link = this.formData.link;
    if (this.idea.id) {
      await ideaService
        .putIdea(this.idea, this.authHeaderTyp)
        .then((queryResult) => {
          this.$emit('updateData', queryResult);
        });
    } else if (this.taskId) {
      await ideaService
        .postIdea(this.taskId, this.idea, this.authHeaderTyp)
        .then((queryResult) => {
          this.$emit('updateData', queryResult);
        });
    }
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
    this.isSaving = false;
  }
}
</script>

<style scoped></style>
