<template>
  <ValidationForm
    :form-data="formData"
    :use-default-submit="false"
    v-on:submitDataValid="save"
    v-on:reset="reset"
  >
    <el-dialog v-model="showDialog" :before-close="handleClose">
      <template #header>
        <span class="el-dialog__title">{{
          sessionId
            ? $t('moderator.organism.settings.sessionSettings.headerEdit')
            : $t('moderator.organism.settings.sessionSettings.header')
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
      <template #footer>
        <FromSubmitItem
          :form-state-message="formData.stateMessage"
          submit-label-key="moderator.organism.settings.sessionSettings.submit"
          :disabled="isSaving"
        />
      </template>
    </el-dialog>
  </ValidationForm>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

import * as sessionService from '@/services/session-service';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import { ValidationData } from '@/types/ui/ValidationRule';
import { Session } from '@/types/api/Session';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
  },
  emits: ['sessionUpdated', 'update:showModal', 'update:sessionId'],
})
export default class SessionSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop({ default: false }) showModal!: boolean;
  @Prop({}) sessionId!: string;

  today = new Date();

  formData: ValidationData = {
    title: '',
    description: '',
    expirationDate: new Date(this.today.setMonth(this.today.getMonth() + 1)),
  };

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  sessionCash!: cashService.SimplifiedCashEntry<Session>;
  @Watch('sessionId', { immediate: true })
  onSessionIdChanged(id: string): void {
    if (id) {
      this.sessionCash = sessionService.registerGetById(
        this.sessionId,
        this.updateSession,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    } else {
      this.reset();
    }
  }

  updateSession(session: Session): void {
    this.formData.title = session.title;
    this.formData.description = session.description;
    this.formData.expirationDate = new Date(session.expirationDate);
  }

  unmounted(): void {
    cashService.deregisterAllGet(this.updateSession);
  }

  handleClose(done: { (): void }): void {
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.formData.title = '';
    this.formData.description = '';
    this.formData.expirationDate = new Date(
      this.today.setMonth(this.today.getMonth() + 1)
    );
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  get isoExpirationDate(): string {
    this.formData.expirationDate.setHours(1);
    return this.formData.expirationDate.toISOString().slice(0, 10);
  }

  isSaving = false;
  async save(): Promise<void> {
    this.isSaving = true;
    if (!this.sessionId) {
      await sessionService
        .post({
          title: this.formData.title,
          description: this.formData.description,
          expirationDate: this.isoExpirationDate,
        })
        .then(
          (session) => {
            this.$emit('update:showModal', false);
            this.$emit('sessionUpdated');
            this.reset();
            this.$router.push({
              name: 'moderator-session-details',
              params: {
                sessionId: session.id,
              },
            });
          },
          (error) => {
            this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
          }
        );
    } else {
      await sessionService
        .put({
          id: this.sessionId,
          title: this.formData.title,
          description: this.formData.description,
          expirationDate: this.isoExpirationDate,
        })
        .then(
          () => {
            this.$emit('update:showModal', false);
            this.$emit('update:sessionId', null);
            this.$emit('sessionUpdated');
            if (this.sessionCash) this.sessionCash.refreshData();
          },
          (error) => {
            this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
          }
        );
    }
    this.isSaving = false;
  }
}
</script>

<style lang="scss" scoped></style>
