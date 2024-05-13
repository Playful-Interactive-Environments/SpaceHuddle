<template>
  <el-dialog
    v-model="showSettings"
    :before-close="handleClose"
    width="calc(var(--app-width) * 0.8)"
  >
    <template #header>
      <span class="el-dialog__title">
        {{ $t('moderator.organism.settings.facilitatorSettings.header') }}
      </span>
      <br />
      <br />
      <p>
        {{ $t('moderator.organism.settings.facilitatorSettings.info') }}
      </p>
    </template>
    <el-table
      v-if="roles && roles.length > 0"
      :data="roles"
      style="width: 100%"
      max-height="250"
    >
      <el-table-column
        prop="username"
        :label="$t('moderator.organism.settings.facilitatorSettings.email')"
      />
      <el-table-column
        :label="$t('moderator.organism.settings.facilitatorSettings.role')"
      >
        <template #default="scope">
          {{ $t(`enum.userType.${scope.row.role}`) }}
        </template>
      </el-table-column>
      <el-table-column width="120">
        <template #default="scope">
          <span v-on:click="editUser(scope.$index)">
            <font-awesome-icon class="icon link" icon="pen" />
          </span>
          <span v-on:click="deleteUser(scope.$index)">
            <font-awesome-icon class="icon link" icon="trash" />
          </span>
        </template>
      </el-table-column>
    </el-table>
    <br />
    <br />
    <ValidationForm
      :form-data="formData"
      :use-default-submit="false"
      v-on:submitDataValid="save"
      v-on:reset="reset"
    >
      <el-form-item
        prop="email"
        :rules="[defaultFormRules.ruleEmail, defaultFormRules.ruleRequired]"
      >
        <span class="layout__level">
          <el-input
            v-model="formData.email"
            name="email"
            autocomplete="on"
            :placeholder="
              $t('moderator.organism.settings.facilitatorSettings.info')
            "
          />
          <span>
            <el-select v-model="formData.role">
              <el-option
                :value="UserType.FACILITATOR"
                :label="$t(`enum.userType.${UserType.FACILITATOR}`)"
              />
              <el-option
                :value="UserType.MODERATOR"
                :label="$t(`enum.userType.${UserType.MODERATOR}`)"
              />
            </el-select>
          </span>
          <span style="margin-right: 0">
            <el-button type="primary" native-type="submit" circle>
              <font-awesome-icon icon="check" style="font-size: 1.5rem" />
            </el-button>
          </span>
        </span>
      </el-form-item>
      <el-form-item
        :label="
          $t('moderator.organism.settings.facilitatorSettings.allowAnonymous')
        "
        prop="allowAnonymous"
        class="allow"
      >
        <el-switch v-model="formData.allowAnonymous" />
        <router-link
          v-if="formData.allowAnonymous"
          :to="`/public-screen/${sessionId}/everyone`"
          target="_blank"
        >
          <el-button type="info">
            <template #icon>
              <font-awesome-icon :icon="['fac', 'presentation']" />
            </template>
            {{
              $t('moderator.organism.settings.facilitatorSettings.publicScreen')
            }}
          </el-button>
        </router-link>
      </el-form-item>
      <el-form-item
        prop="stateMessage"
        :rules="[defaultFormRules.ruleStateMessage]"
      >
        <el-input
          v-model="formData.stateMessage"
          class="hide"
          input-style="display: none"
        />
      </el-form-item>
    </ValidationForm>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import myUpload from 'vue-image-crop-upload/upload-3.vue';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import { ValidationData } from '@/types/ui/ValidationRule';
import ValidationForm, {
  ValidationFormCall,
} from '@/components/shared/molecules/ValidationForm.vue';
import FromSubmitItem from '@/components/shared/molecules/FromSubmitItem.vue';
import * as sessionRoleService from '@/services/session-role-service';
import * as sessionService from '@/services/session-service';
import { SessionRole } from '@/types/api/SessionRole';
import UserType from '@/types/enum/UserType';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import * as authService from '@/services/auth-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Session } from '@/types/api/Session';
import * as cashService from '@/services/cash-service';

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
    'my-upload': myUpload,
  },
  emits: ['update:showModal'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class FacilitatorSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: '' }) sessionId!: string;

  session!: Session;
  roles: SessionRole[] = [];
  own = '';

  formData: ValidationData = {
    email: '',
    role: UserType.FACILITATOR,
    allowAnonymous: false,
  };

  showSettings = false;
  UserType = UserType;

  mounted(): void {
    this.own = authService.getUserData() || '';
    this.reset();
  }

  handleClose(done: { (): void }): void {
    this.reset();
    done();
    this.$emit('update:showModal', false);
  }

  reset(): void {
    this.formData.email = '';
    this.formData.role = UserType.FACILITATOR;
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
    this.reset();
  }

  roleCash!: cashService.SimplifiedCashEntry<SessionRole[]>;
  @Watch('sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    this.deregisterAll();
    sessionService.registerGetById(
      this.sessionId,
      this.updateSession,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
    this.roleCash = sessionRoleService.registerGetList(
      this.sessionId,
      this.updateRole,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  updateSession(session: Session): void {
    this.session = session;
    this.formData.allowAnonymous = session.allowAnonymous;
    this.dataLoaded = true;
  }

  updateRole(roles: SessionRole[]): void {
    this.roles = roles.filter(
      (role) => role.username !== this.own && role.role !== UserType.OWNER
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateSession);
    cashService.deregisterAllGet(this.updateRole);
  }

  unmounted(): void {
    this.deregisterAll();
  }
  dataLoaded = false;

  @Watch('formData.allowAnonymous', { immediate: true })
  async onAllowAnonymousChanged(): Promise<void> {
    if (this.dataLoaded) {
      await this.saveAnonymous();
    }
  }

  async saveAnonymous(): Promise<void> {
    if (this.session.allowAnonymous !== this.formData.allowAnonymous) {
      this.session.allowAnonymous = this.formData.allowAnonymous;
      sessionService.put(this.session);
    }
  }

  async save(): Promise<void> {
    if (!this.roles.find((role) => role.username === this.formData.email)) {
      await sessionRoleService
        .post(this.sessionId, {
          username: this.formData.email,
          role: this.formData.role,
        })
        .then(
          (data) => {
            this.roles.push(data);
            this.roleCash.refreshData();
            this.reset();
          },
          (error) => {
            this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
          }
        );
    } else {
      await sessionRoleService
        .put(this.sessionId, {
          username: this.formData.email,
          role: this.formData.role,
        })
        .then((data) => {
          const role = this.roles.find(
            (role) => role.username === this.formData.email
          );
          if (role) role.role = data.role;
          this.roleCash.refreshData();
          this.reset();
        });
    }
  }

  async deleteUser(index: number): Promise<void> {
    await sessionRoleService
      .remove(this.sessionId, this.roles[index].username)
      .then((result) => {
        if (result) {
          const index = this.roles.findIndex(
            (role) => role.username === this.formData.email
          );
          if (index > -1) this.roles.splice(index, 1);
          this.roleCash.refreshData();
        }
      });
  }

  async editUser(index: number): Promise<void> {
    this.formData.email = this.roles[index].username;
    this.formData.role = this.roles[index].role;
  }
}
</script>

<style lang="scss" scoped>
.el-form-item .el-form-item {
  margin-bottom: 1rem;
}

.el-button.is-circle {
  padding: 0.7rem;
}

.awesome-icon {
  margin-left: 0.5em;
}

.el-table::v-deep(.cell) {
  span {
    margin-right: 0.5rem;
  }
}

.allow.el-form-item::v-deep(.el-form-item__content) {
  .el-form-item__content {
    display: flex;
    justify-content: space-between;
  }
}
</style>
