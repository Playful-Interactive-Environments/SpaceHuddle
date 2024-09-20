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
        v-if="templates.length > 0 && !sessionId"
        :label="$t('moderator.organism.settings.sessionSettings.template')"
      >
        <el-scrollbar always>
          <div class="flex-content">
            <el-card
              v-for="item of templates"
              :key="item.id"
              shadow="hover"
              class="template"
              :class="{ selected: template?.id === item.id }"
              @click="template = item"
            >
              <div class="title">{{ item.title }}</div>
              <div class="descending">{{ item.description }}</div>
            </el-card>
            <AddItem
              :text="$t('moderator.organism.settings.sessionSettings.empty')"
              :isColumn="true"
              @addNew="template = null"
              :class="{ selected: !template }"
              class="template"
            />
          </div>
        </el-scrollbar>
      </el-form-item>
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
        prop="subject"
        :label="$t('moderator.organism.settings.sessionSettings.subject')"
        :rules="[defaultFormRules.ruleToLong(25)]"
      >
        <el-select
          v-model="formData.subject"
          allow-create
          filterable
          clearable
          :placeholder="
            $t('moderator.organism.settings.sessionSettings.subjectExample')
          "
        >
          <template v-slot:prefix>
            <font-awesome-icon icon="sort" class="el-icon" />
          </template>
          <el-option
            v-for="subject in subjectList"
            :key="subject"
            :value="subject"
            :label="subject"
          >
            <span>
              {{ subject }}
            </span>
          </el-option>
        </el-select>
      </el-form-item>
      <el-form-item
        v-if="isAdmin"
        prop="visibility"
        :label="$t('moderator.organism.settings.sessionSettings.visibility')"
      >
        <el-select v-model="formData.visibility">
          <el-option
            v-for="key in SessionVisibilityType"
            :key="key"
            :value="key"
            :label="$t(`enum.sessionVisibilityType.${key}`)"
          />
        </el-select>
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
      <el-form-item
        :label="$t('moderator.organism.settings.sessionSettings.theme')"
        prop="theme"
      >
        <el-select v-model="formData.theme">
          <el-option
            value=""
            :label="$t('moderator.organism.settings.sessionSettings.default')"
          />
          <el-option
            value="calendar"
            :label="$t('moderator.organism.settings.sessionSettings.calendar')"
          />
        </el-select>
      </el-form-item>
      <el-form-item
        :label="
          $t('moderator.organism.settings.sessionSettings.topicActivation')
        "
        prop="topicActivation"
      >
        <el-select v-model="formData.topicActivation">
          <el-option
            v-for="key in TopicActivation"
            :key="key"
            :value="key"
            :label="$t(`enum.topicActivation.${key}`)"
          />
        </el-select>
      </el-form-item>
      <el-form-item
        v-if="showCoins"
        :label="
          $t('moderator.organism.settings.sessionSettings.startingPoints')
        "
        prop="startingPoints"
      >
        <el-input-number
          v-model="formData.startingPoints"
          :min="0"
          :max="1000"
          :step="100"
        />
      </el-form-item>
      <el-form-item
        v-if="!sessionId"
        prop="customKey"
        :label="$t('moderator.organism.settings.sessionSettings.customKey')"
      >
        <el-switch v-model="hasAutoKey" />
        <el-input v-if="!hasAutoKey" v-model="formData.connectionKey" />
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
import { TopicActivation } from '@/types/enum/TopicActivation';
import { SessionVisibilityType } from '@/types/enum/SessionVisibilityType';
import { getUserRole } from '@/services/auth-service';
import { UserRoleType } from '@/types/enum/UserRoleType';
import AddItem from '@/components/moderator/atoms/AddItem.vue';

@Options({
  components: {
    AddItem,
    ValidationForm,
    FromSubmitItem,
  },
  emits: [
    'sessionUpdated',
    'update:showModal',
    'update:sessionId',
    'update:subject',
  ],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SessionSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop({ default: false }) showModal!: boolean;
  @Prop({}) sessionId!: string;

  today = new Date();
  subjectList: string[] = [];
  subjectState = false;
  sessionCash!: cashService.SimplifiedCashEntry<Session>;
  subjectCash!: cashService.SimplifiedCashEntry<string[]>;
  hasAutoKey = true;
  session: Session | null = null;
  templates: Session[] = [];
  template: Session | null = null;

  formData: ValidationData = {
    title: '',
    description: '',
    subject: '',
    theme: '',
    topicActivation: TopicActivation.ALWAYS,
    visibility: SessionVisibilityType.PRIVATE,
    startingPoints: 0,
    connectionKey: null,
    expirationDate: new Date(this.today.setMonth(this.today.getMonth() + 1)),
  };

  TopicActivation = TopicActivation;
  SessionVisibilityType = SessionVisibilityType;

  get showCoins(): boolean {
    return JSON.parse(process.env.VUE_APP_SHOW_COINS);
  }

  get isAdmin(): boolean {
    return getUserRole() === UserRoleType.ADMIN;
  }

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }
  @Watch('sessionId', { immediate: true })
  onSessionIdChanged(id: string): void {
    this.deregisterAll();
    if (id) {
      this.sessionCash = sessionService.registerGetById(
        this.sessionId,
        this.updateSession,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
      this.subjectCash = sessionService.registerGetSubjects(
        this.updateSubjects,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
      this.template = null;
    } else {
      this.subjectCash = sessionService.registerGetSubjects(
        this.updateSubjects,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
      this.reset();
    }
  }

  @Watch('template', { immediate: true })
  onTemplateChanged(): void {
    if (this.template) {
      this.formData.title = this.template.title;
      this.formData.description = this.template.description;
      this.formData.subject = this.template.subject;
      this.formData.theme = this.template.theme ?? '';
      this.formData.topicActivation = this.template.topicActivation ?? '';
      this.formData.visibility = SessionVisibilityType.PRIVATE;
      this.formData.startingPoints =
        this.template.parameter?.startingPoints ?? 0;
    } else {
      this.reset();
    }
  }

  updateSession(session: Session): void {
    this.session = session;
    this.formData.title = session.title;
    this.formData.description = session.description;
    this.subjectCash.refreshData();
    this.formData.subject = session.subject;
    this.formData.theme = session.theme ?? '';
    this.formData.topicActivation = session.topicActivation ?? '';
    this.formData.visibility =
      session.visibility ?? SessionVisibilityType.PRIVATE;
    this.formData.startingPoints = session.parameter?.startingPoints ?? 0;
    this.formData.connectionKey = session.connectionKey;
    this.formData.expirationDate = new Date(session.expirationDate);
  }

  updateSubjects(subjects: string[]): void {
    this.subjectList = subjects;
    this.removeNullEntries(this.subjectList);
  }

  updateTemplates(list: Session[]): void {
    this.templates = list;
  }

  removeNullEntries(subjectList: string[]): void {
    const tempList: string[] = [];
    subjectList.forEach((subject) => {
      if (subject != null || subject != undefined) {
        tempList.push(subject);
      }
    });
    this.subjectList = tempList;
  }

  mounted(): void {
    sessionService.registerGetTemplateList(
      this.updateTemplates,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateSession);
    cashService.deregisterAllGet(this.updateSubjects);
    cashService.deregisterAllGet(this.updateTemplates);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  handleClose(done: { (): void }): void {
    done();
    this.reset();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.template = null;
    if (!this.sessionId) {
      this.hasAutoKey = true;
      this.formData.title = '';
      this.formData.description = '';
      this.formData.subject = null;
      this.formData.theme = null;
      this.formData.topicActivation = TopicActivation.ALWAYS;
      this.formData.visibility = SessionVisibilityType.PRIVATE;
      this.formData.startingPoints = 0;
      this.formData.connectionKey = null;
      this.formData.expirationDate = new Date(
        this.today.setMonth(this.today.getMonth() + 1)
      );
      this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
    } else if (this.session) {
      this.formData.title = this.session.title;
      this.formData.description = this.session.description;
      this.formData.subject = this.session.subject;
      this.formData.theme = this.session.theme ?? '';
      this.formData.topicActivation = this.session.topicActivation ?? '';
      this.formData.visibility =
        this.session.visibility ?? SessionVisibilityType.PRIVATE;
      this.formData.startingPoints =
        this.session.parameter?.startingPoints ?? 0;
      this.formData.connectionKey = this.session.connectionKey;
      this.formData.expirationDate = new Date(this.session.expirationDate);
    }
  }

  get isoExpirationDate(): string {
    this.formData.expirationDate.setHours(1);
    return this.formData.expirationDate.toISOString().slice(0, 10);
  }

  isSaving = false;
  async save(): Promise<void> {
    this.isSaving = true;
    if (!this.sessionId) {
      if (
        this.formData.subject === '' ||
        this.formData.subject === null ||
        this.formData.subject === undefined
      ) {
        this.formData.subject = null;
        this.subjectState = false;
      } else {
        this.subjectState = true;
      }
      const data: any = {
        title: this.formData.title,
        description: this.formData.description,
        subject: this.formData.subject,
        theme: this.formData.theme,
        topicActivation: this.formData.topicActivation,
        visibility: this.formData.visibility,
        expirationDate: this.isoExpirationDate,
        parameter: this.showCoins
          ? { startingPoints: this.formData.startingPoints }
          : {},
      };
      if (!this.hasAutoKey && this.formData.connectionKey)
        data.connectionKey = this.formData.connectionKey;
      const result = this.template
        ? sessionService.createFromTemplate(this.template.id, data)
        : sessionService.post(data);
      await result.then(
        (session) => {
          this.$emit('update:showModal', false);
          this.$emit('update:subject', this.subjectState);
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
      if (
        this.formData.subject === '' ||
        this.formData.subject === null ||
        this.formData.subject === undefined
      ) {
        this.formData.subject = null;
        this.subjectState = false;
      } else {
        this.subjectState = true;
      }
      await sessionService
        .put({
          id: this.sessionId,
          title: this.formData.title,
          description: this.formData.description,
          subject: this.formData.subject,
          theme: this.formData.theme,
          topicActivation: this.formData.topicActivation,
          visibility: this.formData.visibility,
          expirationDate: this.isoExpirationDate,
          parameter: this.showCoins
            ? { startingPoints: this.formData.startingPoints }
            : {},
        })
        .then(
          () => {
            this.$emit('update:showModal', false);
            this.$emit('update:sessionId', null);
            this.$emit('update:subject', this.subjectState);
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

<style lang="scss" scoped>
.template {
  color: var(--color-highlight-dark);
  border-color: var(--color-highlight-dark);
  cursor: pointer;
  min-height: calc(110rem / 16);
  height: 100%;
  background-color: unset;
}

.flex-content {
  display: flex;
  margin-bottom: 1rem;

  .el-card {
    flex-shrink: 0;
    width: 15rem;
    margin: 0 0.5rem;

    .title {
      font-weight: var(--font-weight-semibold);
      font-size: var(--font-size-large);
    }
  }
}

.selected {
  background-color: white;
}
</style>
