<template>
  <el-dialog v-model="showSettings" :before-close="handleClose" width="80vw">
    <template #title>
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
          <span style="margin-right: 0">
            <el-button type="primary" native-type="submit" circle>
              <font-awesome-icon icon="check" style="font-size: 1.5rem" />
            </el-button>
          </span>
        </span>
      </el-form-item>
      <el-form-item
        prop="stateMessage"
        :rules="[defaultFormRules.ruleStateMessage]"
      >
        <el-input v-model="formData.stateMessage" style="display: none" />
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
import { SessionRole } from '@/types/api/SessionRole';
import UserType from '@/types/enum/UserType';
import { getSingleTranslatedErrorMessage } from '@/services/exception-service';
import * as authService from '@/services/auth-service';

@Options({
  components: {
    ValidationForm,
    FromSubmitItem,
    'my-upload': myUpload,
  },
  emits: ['update:showModal'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class LinkSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: '' }) sessionId!: string;

  roles: SessionRole[] = [];
  own = '';

  formData: ValidationData = {
    email: '',
  };

  showSettings = false;

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
    this.formData.call = ValidationFormCall.CLEAR_VALIDATE;
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
    this.reset();
  }

  @Watch('sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    await this.loadRoles();
  }

  async loadRoles(): Promise<void> {
    if (this.sessionId) {
      sessionRoleService.getList(this.sessionId).then((roles) => {
        this.roles = roles.filter((role) => role.username !== this.own);
      });
    }
  }

  async save(): Promise<void> {
    if (!this.roles.find((role) => role.username === this.formData.email)) {
      await sessionRoleService
        .post(this.sessionId, {
          username: this.formData.email,
          role: UserType.FACILITATOR,
        })
        .then(
          (data) => {
            this.roles.push(data);
            this.reset();
          },
          (error) => {
            this.formData.stateMessage = getSingleTranslatedErrorMessage(error);
          }
        );
    }
  }

  async deleteUser(index: number): Promise<void> {
    await sessionRoleService
      .remove(this.sessionId, this.roles[index].username)
      .then((result) => {
        if (result) this.loadRoles();
      });
  }

  async editUser(index: number): Promise<void> {
    this.formData.email = this.roles[index].username;
  }
}
</script>

<style lang="scss" scoped>
.el-form-item .el-form-item {
  margin-bottom: 1rem;
}

.el-button.is-circle {
  padding: 0.5rem;
}

.icon {
  margin-left: 0.5em;
}
</style>