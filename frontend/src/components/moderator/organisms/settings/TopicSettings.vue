<template>
  <ValidationForm
    :form-data="formData"
    :use-default-submit="false"
    v-on:submitDataValid="save"
    v-on:reset="reset"
  >
    <el-dialog v-model="showDialog" :before-close="handleClose">
      <template #title>
        <span class="el-dialog__title">{{
          $t('moderator.organism.settings.topicSettings.header')
        }}</span>
        <br />
        <br />
        <p>
          {{ $t('moderator.organism.settings.topicSettings.info') }}
        </p>
      </template>

      <el-form-item
        prop="title"
        :label="$t('moderator.organism.settings.topicSettings.title')"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.ruleToLong(255),
        ]"
      >
        <el-input
          v-model="formData.title"
          :placeholder="
            $t('moderator.organism.settings.topicSettings.titleExample')
          "
        />
      </el-form-item>
      <el-form-item
        prop="description"
        :label="$t('moderator.organism.settings.topicSettings.description')"
        :rules="[defaultFormRules.ruleToLong(1000)]"
      >
        <el-input
          type="textarea"
          v-model="formData.description"
          rows="3"
          :placeholder="
            $t('moderator.organism.settings.topicSettings.descriptionExample')
          "
        />
      </el-form-item>
      <template #footer>
        <FromSubmitItem
          :form-state-message="formData.stateMessage"
          submit-label-key="moderator.organism.settings.topicSettings.submit"
        />
      </template>
    </el-dialog>
  </ValidationForm>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as topicService from '@/services/topic-service';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { ValidationData } from '@/types/ui/ValidationRule';

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
  },
  emits: ['topicUpdated', 'update:showModal', 'update:topicId'],
})
export default class TopicSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop({ default: false }) showModal!: boolean;
  @Prop({}) sessionId!: string;
  @Prop({}) topicId!: string;

  formData: ValidationData = {
    title: '',
    description: '',
  };

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  @Watch('topicId', { immediate: true })
  onTopicIdChanged(id: string): void {
    if (id) {
      topicService.getTopicById(id).then((topic) => {
        this.formData.title = topic.title;
        this.formData.description = topic.description;
      });
    } else {
      this.reset();
    }
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.formData.title = '';
    this.formData.description = '';
    this.$emit('update:topicId', null);
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  async save(): Promise<void> {
    if (!this.topicId) {
      if (this.sessionId) {
        topicService
          .postTopic(this.sessionId, {
            title: this.formData.title,
            description: this.formData.description,
          })
          .then(
            () => {
              this.$emit('update:showModal', false);
              this.$emit('topicUpdated');
              this.reset();
            },
            (error) => {
              this.formData.stateMessage =
                getSingleTranslatedErrorMessage(error);
            }
          );
      }
    } else {
      topicService
        .putTopic(this.topicId, {
          title: this.formData.title,
          description: this.formData.description,
        })
        .then(
          () => {
            this.$emit('update:showModal', false);
            this.$emit('update:topicId', null);
            this.$emit('topicUpdated');
            //this.reset();
          },
          (error) => {
            this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
          }
        );
    }
  }
}
</script>

<style lang="scss" scoped></style>
