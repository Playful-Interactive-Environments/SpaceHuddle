<template>
  <el-dialog
    v-model="showSettings"
    :before-close="handleClose"
    width="calc(var(--app-width) * 0.8)"
  >
    <template #header>
      <span class="el-dialog__title">
        {{ $t('moderator.organism.settings.participantSettings.header') }}
      </span>
      <br />
      <br />
      <p>
        {{ $t('moderator.organism.settings.participantSettings.info') }}
      </p>
    </template>
    <el-table
      v-if="participants && participants.length > 0"
      :data="participants"
      style="width: 100%"
      max-height="250"
    >
      <el-table-column
        :label="$t('moderator.organism.settings.participantSettings.avatar')"
      >
        <template #default="scope">
          <font-awesome-icon
            :icon="scope.row.avatar.symbol"
            :style="{ color: scope.row.avatar.color }"
          ></font-awesome-icon>
        </template>
      </el-table-column>
      <el-table-column
        prop="browserKey"
        :label="
          $t('moderator.organism.settings.participantSettings.browserKey')
        "
      />
      <el-table-column
        prop="ideaCount"
        :label="$t('moderator.organism.settings.participantSettings.ideaCount')"
      />
      <el-table-column
        prop="voteCount"
        :label="$t('moderator.organism.settings.participantSettings.voteCount')"
      />
      <el-table-column width="120">
        <template #default="scope">
          <span
            v-on:click="deleteParticipant(scope.$index)"
            v-if="scope.row.ideaCount === 0 && scope.row.voteCount === 0"
          >
            <font-awesome-icon class="icon link" icon="trash" />
          </span>
        </template>
      </el-table-column>
    </el-table>
    <br />
    <br />
    <el-button class="fullwidth" v-on:click="add" :disabled="!dataLoaded">
      <template #icon>
        <font-awesome-icon icon="circle-plus" />
      </template>
      {{ $t('moderator.organism.settings.participantSettings.add') }}
    </el-button>
  </el-dialog>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import * as sessionService from '@/services/session-service';
import * as participantService from '@/services/participant-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { Session } from '@/types/api/Session';
import * as cashService from '@/services/cash-service';
import { ParticipantInfo } from '@/types/api/Participant';

@Options({
  emits: ['update:showModal'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class LinkSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: '' }) sessionId!: string;

  session!: Session;
  participants: ParticipantInfo[] = [];

  showSettings = false;

  handleClose(done: { (): void }): void {
    done();
    this.$emit('update:showModal', false);
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
  }

  participantCash!: cashService.SimplifiedCashEntry<ParticipantInfo[]>;
  @Watch('sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    this.deregisterAll();
    sessionService.registerGetById(
      this.sessionId,
      this.updateSession,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
    this.participantCash = sessionService.registerGetParticipants(
      this.sessionId,
      this.updateParticipants,
      EndpointAuthorisationType.MODERATOR,
      60 * 60
    );
  }

  updateSession(session: Session): void {
    this.session = session;
    this.dataLoaded = true;
  }

  updateParticipants(participants: ParticipantInfo[]): void {
    this.participants = participants;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateSession);
    cashService.deregisterAllGet(this.updateParticipants);
  }

  unmounted(): void {
    this.deregisterAll();
  }
  dataLoaded = false;

  async add(): Promise<void> {
    participantService
      .connect(this.session.connectionKey)
      .then(() => this.participantCash.refreshData());
  }

  async deleteParticipant(index: number): Promise<void> {
    await participantService
      .remove(this.participants[index].id)
      .then((result) => {
        if (result) {
          this.participantCash.refreshData();
        }
      });
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
