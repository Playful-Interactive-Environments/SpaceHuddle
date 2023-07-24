<template>
  <ModeratorNavigationLayout>
    <template v-slot:content>
      <h1>{{ $t('moderator.organism.session.overview.header') }}</h1>
      <SessionFilter v-model="filter" />
      <div class="columns is-multiline is-9">
        <div class="column">
          <TutorialStep step="add" type="sessionOverview" :order="0">
            <AddItem
              :text="$t('moderator.organism.session.overview.add')"
              :isColumn="true"
              @addNew="showSettings = true"
            />
          </TutorialStep>
        </div>
        <div
          v-for="session in filteredSessions"
          :key="session.id"
          class="column"
        >
          <SessionCard :session="session" @updated="refreshSessions" />
        </div>
        <div
          class="column"
          v-if="sessions.length - filteredSessions.length !== 0"
        >
          <AddItem
            :text="
              sessions.length -
              filteredSessions.length +
              $t('moderator.organism.session.overview.hidden')
            "
            :display-plus="false"
            :is-column="true"
            @addNew="resetFilters"
          />
        </div>
      </div>
      <div class="session-overview__session-container"></div>
      <SessionSettings
        v-model:show-modal="showSettings"
        @sessionUpdated="refreshSessions"
      />
    </template>
  </ModeratorNavigationLayout>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Session } from '@/types/api/Session';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import SessionCard from '@/components/moderator/organisms/cards/SessionCard.vue';
import SessionSettings from '@/components/moderator/organisms/settings/SessionSettings.vue';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import * as sessionService from '@/services/session-service';
import ModeratorNavigationLayout from '@/components/moderator/organisms/layout/ModeratorNavigationLayout.vue';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import SessionFilter, {
  defaultFilterData,
  SessionFilterData,
} from '@/components/moderator/molecules/SessionFilter.vue';
import SessionSortOrder from '@/types/enum/SessionSortOrder';
import { Watch } from 'vue-property-decorator';

@Options({
  components: {
    SessionFilter,
    TutorialStep,
    AddItem,
    SessionCard,
    SessionSettings,
    SessionCode,
    ModeratorNavigationLayout,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorSessionOverview extends Vue {
  sessions: Session[] = [];
  filteredSessions: Session[] = [];
  showSettings = false;
  filter: SessionFilterData = { ...defaultFilterData };
  errors: string[] = [];
  sessionCash!: cashService.SimplifiedCashEntry<Session[]>;
  async mounted(): Promise<void> {
    this.sessionCash = sessionService.registerGetList(
      this.updateSessions,
      EndpointAuthorisationType.MODERATOR,
      2 * 60
    );
  }
  @Watch('filter.textFilter', { immediate: true })
  onTextFilterChanged(): void {
    this.updateFilteredSessions(this.sessions);
  }
  @Watch('filter.orderType', { immediate: true })
  onOrderTypeChanged(): void {
    this.updateFilteredSessions(this.sessions);
  }
  @Watch('filter.orderAsc', { immediate: true })
  onOrderAscChanged(): void {
    this.updateFilteredSessions(this.sessions);
  }
  @Watch('filter.subjects', { immediate: true })
  onSubjectsChanged(): void {
    if (this.filter.subjects?.length === 0) {
      this.filter.subjects = null;
    }
    this.updateFilteredSessions(this.sessions);
  }
  updateSessions(): void {
    this.sessions = this.sessionCash.data;
    this.filteredSessions = this.sessions;
    if (this.sessions) {
      this.updateFilteredSessions(this.sessions);
    }
  }
  updateFilteredSessions(sessions: Session[]): void {
    const orderType = this.filter.orderType;
    const dataList = sessionService.getOrderGroups(
      sessions,
      this.filter.orderAsc
    );
    switch (orderType) {
      case SessionSortOrder.CHRONOLOGICAL:
      case SessionSortOrder.ALPHABETICAL:
      case SessionSortOrder.TOPICS:
      case SessionSortOrder.TASKS:
      case SessionSortOrder.MODERATORS:
      case SessionSortOrder.PARTICIPANTS:
        this.filteredSessions = sessionService.filterSessions(
          dataList[orderType.toUpperCase()].sessions,
          this.filter.textFilter,
          this.filter.subjects
        );
        break;
      default:
        this.filteredSessions = this.sessions;
    }
  }
  resetFilters(): void {
    this.filter.textFilter = '';
    this.filter.subjects = null;
  }
  refreshSessions(): void {
    this.sessionCash.refreshData();
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateSessions);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>
@import '~@/assets/styles/breakpoints.scss';

.column {
  max-width: 25rem;
  min-width: 20rem;
}

.session-overview {
  background-color: var(--color-background);

  &__session-container {
    width: 100%;
    display: grid;
    grid-gap: 2rem;
    grid-template-columns: 1fr 1fr 1fr;

    @include xxl {
      grid-template-columns: 1fr 1fr 1fr 1fr;
    }
  }
}
</style>
