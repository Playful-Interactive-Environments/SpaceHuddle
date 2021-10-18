<template>
  <ValidationForm
    :form-data="formData"
    :use-default-submit="false"
    v-on:submitDataValid="save"
    v-on:reset="reset"
  >
    <el-dialog v-model="showSettings" width="80vw" :before-close="handleClose">
      <template #title>
        <span class="el-dialog__title">{{
          $t('moderator.organism.settings.ideaSettings.header')
        }}</span>
      </template>
      <el-form-item
        :label="$t('moderator.organism.settings.ideaSettings.keywords')"
        prop="keywords"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.ruleToLong(36),
        ]"
      >
        <el-input
          v-model="formData.keywords"
          :placeholder="
            $t('moderator.organism.settings.ideaSettings.keywordsExample')
          "
        />
      </el-form-item>
      <el-form-item
        :label="$t('moderator.organism.settings.ideaSettings.description')"
        prop="description"
        :rules="[defaultFormRules.ruleToLong(255)]"
      >
        <el-input
          type="textarea"
          v-model="formData.description"
          rows="4"
          :placeholder="
            $t('moderator.organism.settings.ideaSettings.descriptionExample')
          "
        />
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
      <template #footer>
        <FromSubmitItem
          :form-state-message="formData.stateMessage"
          submit-label-key="moderator.organism.settings.ideaSettings.submit"
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

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
    ImagePicker,
    'my-upload': myUpload,
  },
  emits: ['update:showModal'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop() idea!: Idea;

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
    this.formData.description = this.idea.description;
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
      this.formData.description = idea.description;
      this.formData.image = idea.image;
      this.formData.link = idea.link;
    }
  }

  async save(): Promise<void> {
    this.idea.keywords = this.formData.keywords;
    this.idea.description = this.formData.description;
    this.idea.image = this.formData.image;
    this.idea.link = this.formData.link;
    await ideaService.putIdea(this.idea.id, this.idea);
    this.reset();
    this.showSettings = false;
    this.$emit('update:showModal', false);
  }
}
</script>

<style scoped></style>
