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
          $t('moderator.organism.settings.sessionSettings.header')
        }}</span>
        <br />
        <br />
        <p>
          {{ $t('moderator.organism.settings.sessionSettings.info') }}
        </p>
      </template>

      <el-form-item
        prop="title"
        :label="$t('moderator.organism.settings.sessionSettings.title')"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.ruleToLong(255),
        ]"
      >
        <el-input
          v-model="formData.title"
          :placeholder="
            $t('moderator.organism.settings.sessionSettings.titleExample')
          "
        />
      </el-form-item>
      <el-form-item
        prop="description"
        :label="$t('moderator.organism.settings.sessionSettings.description')"
        :rules="[defaultFormRules.ruleToLong(1000)]"
      >
        <el-input
          type="textarea"
          v-model="formData.description"
          rows="3"
          :placeholder="
            $t('moderator.organism.settings.sessionSettings.descriptionExample')
          "
        />
      </el-form-item>
      <el-form-item
        prop="expirationDate"
        :label="
          $t('moderator.organism.settings.sessionSettings.expirationDate')
        "
        :rules="[defaultFormRules.ruleRequired, defaultFormRules.ruleDate]"
      >
        <el-date-picker
          v-model="formData.expirationDate"
          type="date"
          :placeholder="
            $t(
              'moderator.organism.settings.sessionSettings.expirationDatePlaceholder'
            )
          "
        >
        </el-date-picker>
      </el-form-item>
      <el-divider></el-divider>
      <h3 class="heading heading--small">
        {{ $t('moderator.organism.settings.sessionSettings.topics') }}
      </h3>
      <p>
        {{ $t('moderator.organism.settings.sessionSettings.topicsInfo') }}
      </p>
      <el-divider></el-divider>
      <el-form-item
        prop="topic"
        :label="$t('moderator.organism.settings.sessionSettings.firstTopic')"
        :rules="[
          defaultFormRules.ruleRequired,
          defaultFormRules.ruleToLong(255),
        ]"
      >
        <el-input
          v-model="formData.topic"
          :placeholder="
            $t('moderator.organism.settings.sessionSettings.firstTopicExample')
          "
        />
      </el-form-item>
      <template #footer>
        <FromSubmitItem
          :form-state-message="formData.stateMessage"
          submit-label-key="moderator.organism.settings.sessionSettings.submit"
        />
      </template>
    </el-dialog>
  </ValidationForm>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

import * as sessionService from '@/services/session-service';
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
})
export default class SessionSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop({ default: false }) showModal!: boolean;

  maxNrOfTopics = 5;
  today = new Date();

  formData: ValidationData = {
    title: '',
    description: '',
    topic: '',
    expirationDate: new Date(this.today.setMonth(this.today.getMonth() + 1)),
  };

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.formData.title = '';
    this.formData.description = '';
    this.formData.topic = '';
    this.formData.maxNrOfTopics = 5;
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  async save(): Promise<void> {
    sessionService
      .post({
        title: this.formData.title,
        description: this.formData.description,
        maxParticipants: 100,
        expirationDate: this.formData.expirationDate.toISOString().slice(0, 10),
      })
      .then(
        (session) => {
          topicService
            .postTopic(session.id, {
              title: this.formData.topic,
              description:
                'dummy data - this field should be removed in the backend!',
            })
            .then(
              () => {
                //this.$emit('update:showModal', false);
                this.$router.push({
                  name: 'moderator-session-details',
                  params: {
                    sessionId: session.id,
                  },
                });
              },
              (error) => {
                this.formData.stateMessage =
                  getSingleTranslatedErrorMessage(error);
              }
            );
        },
        (error) => {
          this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
        }
      );
  }
}
</script>

<style lang="scss" scoped></style>
