<template>
  <div class="session-overview">
    <Header />
    <main class="container container--spaced container--fullheight">
      <h1>Your Sessions</h1>
      <div class="session-overview__session-container">
        <div
          class="session-overview__session"
          v-for="session in sessions"
          :key="session.id"
        >
          <Card>
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
                  Go to session
                </button>
              </router-link>
            </template>
            <template v-slot:topics>
              <ModuleCount />
            </template>
          </Card>
        </div>
        <AddItem
          text="New Session"
          :isColumn="true"
          @addNew="showModalSessionCreate = true"
        />
        <ModalSessionCreate v-model:show-modal="showModalSessionCreate" />
      </div>
    </main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Session } from '@/services/session-service';
import { formatDate } from '@/utils/date';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import Card from '../../components/shared/atoms/Card.vue';
import Header from '@/components/moderator/organisms/Header.vue';
import ModalSessionCreate from '@/components/shared/molecules/ModalSessionCreate.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import * as sessionService from '@/services/session-service';

@Options({
  components: {
    AddItem,
    Card,
    Header,
    ModalSessionCreate,
    ModuleCount,
    SessionCode,
  },
})
export default class ModeratorSessionOverview extends Vue {
  sessions: Session[] = [];
  showModalSessionCreate = false;

  formatDate = formatDate;

  async mounted(): Promise<void> {
    this.sessions = await sessionService.getList();
  }
}
</script>

<style lang="scss" scoped>
$big-screen-breakpoint: 1300px;
.session-overview {
  background-color: var(--color-background-gray);
  &__session-container {
    width: 100%;
    display: grid;
    grid-gap: 2rem;
    grid-template-columns: 1fr 1fr 1fr;

    @media only screen and (min-width: $big-screen-breakpoint) {
      grid-template-columns: 1fr 1fr 1fr 1fr;
    }
  }

  &__session {
  }
}
</style>
