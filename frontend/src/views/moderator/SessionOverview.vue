<template>
  <div class="session-overview container--fullheight">
    <h1>Your Sessions</h1>
    <div class="grid-container">
      <div class="grid-item" v-for="session in sessions" :key="session.id">
        <Card>
          <template v-slot:date>{{
            formatDate(session.creationDate)
          }}</template>
          <template v-slot:title> {{ session.title }} </template>
          <template v-slot:description>
            short description about the goals of this session and other stuff...
          </template>
          <template v-slot:default>
            <SessionCode :code="session.connectionKey" :hasBorder="true" />
            <router-link :to="session.id">
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
      <AddItem text="New Session" :isColumn="true" @addNew="newSession" />
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import Card from '../../components/shared/atoms/Card.vue';
import ModuleCount from '@/components/moderator/molecules/ModuleCount.vue';
import SessionCode from '@/components/moderator/molecules/SessionCode.vue';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import * as sessionService from '@/services/moderator/session-service';
import { Session } from '@/services/moderator/session-service';

import { formatDate } from '@/utils/date';

@Options({
  components: {
    Card,
    ModuleCount,
    SessionCode,
    AddItem,
  },
})
export default class SessionOverview extends Vue {
  sessions: Session[] = [];

  formatDate = formatDate;

  async mounted(): Promise<void> {
    this.sessions = await sessionService.getList();
  }

  async newSession(): Promise<void> {
    await sessionService.post({
      title: 'New session created',
      description: 'Test description',
      maxParticipants: 100,
      expirationDate: '2021-09-12',
    });
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
