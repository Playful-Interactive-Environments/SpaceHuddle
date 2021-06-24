<template>
  <div class="session-overview container--fullheight">
    <h1>Your Sessions</h1>
    <div class="grid-container">
      <div class="grid-item" v-for="session in sessions" :key="session.id">
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
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import Card from '../../components/shared/atoms/Card.vue';
import ModalSessionCreate from '@/components/shared/molecules/ModalSessionCreate.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import * as sessionService from '@/services/moderator/session-service';
import { Session } from '@/services/moderator/session-service';

import { formatDate } from '@/utils/date';

@Options({
  components: {
    AddItem,
    Card,
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

<style scoped>
.grid-container {
  display: grid;
  grid-gap: 50px 50px;
  grid-template-columns: 1fr 1fr 1fr;
}
.session-overview {
  background: var(--color-background-gray);
  padding: 60px 120px 60px 120px;
}
p {
  font-size: 0.8rem;
}
</style>