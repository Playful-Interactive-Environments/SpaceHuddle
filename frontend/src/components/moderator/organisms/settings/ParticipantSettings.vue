<template>
  <el-dialog
    v-model="showSettings"
    :before-close="handleClose"
    width="calc(var(--app-width) * 0.8)"
  >
    <template #header>
      <span class="el-dialog__title">
        {{ $t('moderator.organism.settings.participantSettings.header') }}
        ( {{ participants.length }} )
        <span @click="() => (showQRCode = true)">
          <font-awesome-icon icon="qrcode" />
        </span>
      </span>
      <br />
      <br />
      <p>
        {{ $t('moderator.organism.settings.participantSettings.info') }}
      </p>
    </template>
    <div v-if="!viewDetailsForParticipant && !showQRCode">
      {{
        $t('moderator.organism.settings.participantSettings.everyoneCanJoin')
      }}
      <el-switch v-if="session" v-model="everyoneCanJoin"></el-switch>
      <el-table
        v-if="participants && participants.length > 0"
        :data="participants"
        style="width: 100%"
        max-height="250"
        v-on:row-click="participantDetails"
      >
        <el-table-column width="50">
          <template #default="scope">
            <el-checkbox
              v-if="isDeletable(scope.row)"
              v-model="scope.row.selected"
            />
          </template>
        </el-table-column>
        <el-table-column
          :label="$t('moderator.organism.settings.participantSettings.avatar')"
          width="80"
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
          :label="
            $t('moderator.organism.settings.participantSettings.ideaCount')
          "
        />
        <el-table-column
          prop="voteCount"
          :label="
            $t('moderator.organism.settings.participantSettings.voteCount')
          "
        />
        <el-table-column width="150">
          <template #default="scope">
            <span v-on:click="mailParticipant(scope.$index)">
              <font-awesome-icon class="icon link" icon="at" />
            </span>
            <span v-on:click="copyParticipant(scope.$index)">
              <font-awesome-icon class="icon link" icon="copy" />
            </span>
            <span
              v-on:click="deleteParticipant(scope.$index)"
              v-if="isDeletable(scope.row)"
            >
              <font-awesome-icon class="icon link" icon="trash" />
            </span>
          </template>
        </el-table-column>
      </el-table>
      <br />
      <br />
      <div class="fullwidth">
        <el-input-number v-model="addCount" :min="1" :max="100" />
        <el-button type="primary" v-on:click="add" :disabled="!dataLoaded">
          <template #icon>
            <font-awesome-icon icon="circle-plus" />
          </template>
          {{ $t('moderator.organism.settings.participantSettings.add') }}
        </el-button>
      </div>
      <el-button type="primary" class="fullwidth" v-on:click="deleteSelected">
        {{
          $t('moderator.organism.settings.participantSettings.removeSelected')
        }}
      </el-button>
      <PDFConverter
        v-if="session"
        :enable-download="false"
        :preview-modal="true"
        :paginate-elements-by-height="600"
        filename="participants"
        ref="html2Pdf"
      >
        <template v-slot:pdf-content>
          <div
            class="pdf"
            v-for="participant in participants"
            :key="participant.id"
          >
            <p>
              {{
                $t(
                  'moderator.organism.settings.participantSettings.connectionInfo'
                )
              }}
            </p>
            <h2>{{ session.title }}</h2>
            <p class="center">
              {{ `${baseJoinLink}${participant.browserKey}` }}
            </p>
            <p>{{ session.description }}</p>
            <div class="details">
              <div>
                <h1>
                  <font-awesome-icon
                    :icon="participant.avatar.symbol"
                    :style="{ color: participant.avatar.color }"
                  ></font-awesome-icon>
                </h1>
              </div>
              <div class="details-right">
                <span>
                  {{ participant.browserKey }}
                </span>
                <QrcodeVue
                  :foreground="contrastColor"
                  background="white"
                  render-as="svg"
                  :value="`${baseJoinLink}${participant.browserKey}`"
                  :size="200"
                  level="H"
                />
              </div>
            </div>
          </div>
        </template>
      </PDFConverter>
      <el-button
        type="primary"
        class="fullwidth"
        v-on:click="generateReport"
        :loading="reportInProgress"
      >
        {{ $t('moderator.organism.settings.participantSettings.export') }}
      </el-button>
    </div>
    <div v-else-if="viewDetailsForParticipant">
      <el-page-header
        :title="$t('general.back')"
        @back="viewDetailsForParticipant = null"
      />
      <div class="details">
        <div>
          <h1>
            <font-awesome-icon
              :icon="viewDetailsForParticipant.avatar.symbol"
              :style="{ color: viewDetailsForParticipant.avatar.color }"
            ></font-awesome-icon>
          </h1>
        </div>
        <div class="details-right">
          <span
            v-on:click="copyToClipboard(viewDetailsForParticipant.browserKey)"
          >
            {{ viewDetailsForParticipant.browserKey }}
          </span>
          <QrcodeVue
            :foreground="contrastColor"
            :background="backgroundColor"
            render-as="svg"
            :value="joinLink"
            :size="200"
            level="H"
            v-on:click="copyToClipboard(joinLink)"
          />
        </div>
      </div>
    </div>
    <div v-else-if="showQRCode">
      <el-page-header :title="$t('general.back')" @back="showQRCode = false" />
      <div class="details">
        <div class="details-left">
          <span v-on:click="copyToClipboard(`${session.connectionKey}.new`)">
            {{ session.connectionKey }}.new
          </span>
          <QrcodeVue
            :foreground="contrastColor"
            :background="backgroundColor"
            render-as="svg"
            :value="getJoinLink(`${session.connectionKey}.new`)"
            :size="200"
            level="H"
            v-on:click="
              copyToClipboard(getJoinLink(`${session.connectionKey}.new`))
            "
          />
        </div>
        <div class="details-right">
          <span v-on:click="copyToClipboard(session.connectionKey)">
            {{ session.connectionKey }}
          </span>
          <QrcodeVue
            :foreground="contrastColor"
            :background="backgroundColor"
            render-as="svg"
            :value="getJoinLink(session.connectionKey)"
            :size="200"
            level="H"
            v-on:click="copyToClipboard(getJoinLink(session.connectionKey))"
          />
        </div>
      </div>
    </div>
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
import QrcodeVue from 'qrcode.vue';
import * as themeColors from '@/utils/themeColors';
import { copyToClipboard } from '@/utils/date';
import PDFConverter from '@/components/shared/atoms/PDFConverter.vue';
import { deleteConfirmDialog } from '@/services/api';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

@Options({
  components: {
    FontAwesomeIcon,
    QrcodeVue,
    PDFConverter,
  },
  emits: ['update:showModal'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantSettings extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;

  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: '' }) sessionId!: string;

  session: Session | null = null;
  participants: ParticipantInfo[] = [];
  viewDetailsForParticipant: ParticipantInfo | null = null;
  everyoneCanJoin = true;
  addCount = 1;

  showSettings = false;
  showQRCode = false;

  get contrastColor(): string {
    return themeColors.getContrastColor();
  }

  get backgroundColor(): string {
    return themeColors.getBackgroundColor();
  }

  get baseJoinLink(): string {
    return `${window.location.origin}/join/`;
  }

  get joinLink(): string {
    return this.getJoinLink(this.viewDetailsForParticipant?.browserKey);
  }

  getJoinLink(key: string | undefined): string {
    if (key) return `${this.baseJoinLink}${key}`;
    return this.baseJoinLink;
  }

  copyToClipboard(text: string): void {
    copyToClipboard(text, this.$t);
  }

  handleClose(done: { (): void }): void {
    this.viewDetailsForParticipant = null;
    done();
    this.$emit('update:showModal', false);
  }

  @Watch('showModal', { immediate: true })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showSettings = showModal;
  }

  @Watch('everyoneCanJoin', { immediate: true })
  onEveryoneCanJoinChanged(): void {
    if (this.session && this.dataLoaded) {
      const everyoneCanJoinDB = this.session.maxParticipants !== 0;
      if (everyoneCanJoinDB !== this.everyoneCanJoin) {
        if (this.everyoneCanJoin) this.session.maxParticipants = null;
        else this.session.maxParticipants = 0;
        sessionService.put(this.session).then(() => {
          if (this.sessionCash) {
            this.sessionCash.refreshData();
          }
        });
      }
    }
  }

  participantCash!: cashService.SimplifiedCashEntry<ParticipantInfo[]>;
  sessionCash!: cashService.SimplifiedCashEntry<Session>;
  @Watch('sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    this.deregisterAll();
    this.sessionCash = sessionService.registerGetById(
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
    const everyoneCanJoin = session.maxParticipants !== 0;
    if (this.everyoneCanJoin !== everyoneCanJoin)
      this.everyoneCanJoin = everyoneCanJoin;
    this.session = session;
    this.dataLoaded = true;
  }

  updateParticipants(participants: ParticipantInfo[]): void {
    this.participants = participants;
    for (const participant of this.participants) {
      (participant as any).selected = false;
    }
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
    if (this.session) {
      participantService
        .addParticipant(this.session.connectionKey, this.addCount)
        .then(() => this.participantCash.refreshData());
    }
  }

  async deleteSelected(): Promise<void> {
    const list: Promise<boolean>[] = [];
    let hasConfirmed = false;
    for (const participant of this.participants) {
      if ((participant as any).selected && this.isDeletable(participant)) {
        if (!hasConfirmed) {
          hasConfirmed = await deleteConfirmDialog();
          if (!hasConfirmed) return;
        }
        list.push(participantService.remove(participant.id, false));
      }
    }
    await Promise.all(list);
    this.participantCash.refreshData();
  }

  isDeletable(participant: ParticipantInfo) {
    return participant.ideaCount === 0 && participant.voteCount === 0;
  }

  async mailParticipant(index: number): Promise<void> {
    const link = `${this.baseJoinLink}${this.participants[index].browserKey}`;
    const body = `${this.$t(
      'moderator.organism.settings.participantSettings.connectionInfo'
    )}

${this.$t('moderator.organism.settings.participantSettings.key')}: ${
      this.participants[index].browserKey
    }
${this.$t('moderator.organism.settings.participantSettings.link')}: ${link}`;

    window.open(
      `mailto:?subject=${this.$t(
        'moderator.organism.settings.participantSettings.connectionSubject'
      )}&body=${encodeURI(body)}`
    );
  }

  async copyParticipant(index: number): Promise<void> {
    this.copyToClipboard(
      `${this.baseJoinLink}${this.participants[index].browserKey}`
    );
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

  participantDetails(
    row: ParticipantInfo,
    column: any,
    //eslint-disable-next-line @typescript-eslint/no-unused-vars
    event: PointerEvent
  ): void {
    if (column.label) {
      this.viewDetailsForParticipant = row;
    }
  }

  reportInProgress = false;
  generateReport(): void {
    this.reportInProgress = true;
    (this.$refs as any).html2Pdf
      .generatePdf()
      .then(() => {
        this.reportInProgress = false;
      })
      .catch(() => {
        this.reportInProgress = false;
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

.details {
  margin: auto;
  display: flex;
  align-items: center;
  flex-direction: row;
  justify-content: space-evenly;

  h1 {
    font-size: 10rem;
    font-weight: var(--font-weight-semibold);
    line-height: 1.8;
  }

  .details-right {
    margin-left: var(--side-padding);
    font-size: 1.35rem;
    font-family: monospace;
    svg {
      display: flex;
    }
  }

  .details-left {
    font-size: 1.35rem;
    font-family: monospace;
    margin: 2rem 0;
    svg {
      display: flex;
    }
  }
}

.pdf {
  margin: 100px;

  h2 {
    font-size: 3rem;
    font-weight: bold;
    text-align: center;
    padding-top: 1rem;
  }

  .center {
    text-align: center;
    padding-bottom: 1rem;
  }

  p {
    font-size: 1rem;
  }
}

.fullwidth {
  display: flex;
  .el-button {
    flex-grow: 1;
    margin-left: 0.5rem;
  }
}
</style>
