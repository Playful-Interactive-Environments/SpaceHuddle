<template>
  <ModeratorNavigationLayout>
    <template v-slot:content>
      <h1>{{ $t('moderator.organism.session.overview.header') }}</h1>
      <div class="columns is-multiline is-9">
        <div v-for="session in sessions" :key="session.id" class="column">
          <SessionCard :session="session"> </SessionCard>
        </div>
        <div class="column">
          <TutorialStep step="add" type="sessionOverview" :order="0">
            <AddItem
              :text="$t('moderator.organism.session.overview.add')"
              :isColumn="true"
              @addNew="showSettings = true"
            />
          </TutorialStep>
        </div>
      </div>
      <div class="session-overview__session-container"></div>
      <SessionSettings
        v-model:show-modal="showSettings"
        @sessionUpdated="getSessions"
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

@Options({
  components: {
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
  showSettings = false;
  errors: string[] = [];
  readonly intervalTime = 10000;
  interval!: any;

  async mounted(): Promise<void> {
    this.getSessions();
    this.startInterval();
  }

  getSessions(): void {
    sessionService.getList().then((queryResult) => {
      this.sessions = queryResult;
    });
  }

  startInterval(): void {
    this.interval = setInterval(this.getSessions, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
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
  background-color: var(--color-background-gray);

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
