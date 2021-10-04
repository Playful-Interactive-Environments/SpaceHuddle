<template>
  <div class="session-overview">
    <Header />
    <main class="container2 container2--spaced container2--fullheight-header">
      <h1>{{ $t('moderator.organism.session.overview.header') }}</h1>
      <div class="session-overview__session-container">
        <SessionCard v-for="session in sessions" :key="session.id">
          <template v-slot:date>
            {{ formatDate(session.creationDate) }}
          </template>
          <template v-slot:title> {{ session.title }} </template>
          <template v-slot:description>
            {{ session.description }}
          </template>
          <template v-slot:default>
            <SessionCode :code="session.connectionKey" :hasBorder="true" />
            <router-link :to="`/session/${session.id}`">
              <button class="btn btn--mint btn--fullwidth">
                {{ $t('moderator.organism.session.overview.select') }}
              </button>
            </router-link>
          </template>
          <template v-slot:topics>
            <ModuleCount />
          </template>
        </SessionCard>
        <AddItem
          :text="$t('moderator.organism.session.overview.add')"
          :isColumn="true"
          @addNew="showSettings = true"
        />
        <SessionSettings v-model:show-modal="showSettings" />
      </div>
    </main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Session } from '@/types/api/Session';
import { formatDate } from '@/utils/date';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import SessionCard from '@/components/moderator/organisms/cards/SessionCard.vue';
import Header from '@/components/moderator/organisms/Header.vue';
import SessionSettings from '@/components/moderator/organisms/settings/SessionSettings.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import * as sessionService from '@/services/session-service';

@Options({
  components: {
    AddItem,
    SessionCard,
    Header,
    SessionSettings,
    ModuleCount,
    SessionCode,
  },
})
export default class ModeratorSessionOverview extends Vue {
  sessions: Session[] = [];
  showSettings = false;
  errors: string[] = [];

  formatDate = formatDate;

  async mounted(): Promise<void> {
    sessionService.getList().then((queryResult) => {
      this.sessions = queryResult;
    });
  }
}
</script>

<style lang="scss" scoped>
@import '~@/assets/styles/breakpoints.scss';

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
