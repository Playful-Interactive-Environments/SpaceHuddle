<template>
  <div class="session-overview container--fullheight">
    <h1>Your Sessions</h1>
    <div class="grid-container">
      <div class="grid-item" v-for="i in 3" :key="i">
        <Card>
          <template v-slot:date> 24.08.2020 </template>
          <template v-slot:title> Marketing Meeting </template>
          <template v-slot:description>
            short description about the goals of this session and other stuff...
          </template>
          <template v-slot:default>
            <SessionCode :hasBorder="true" />
            <button class="btn btn--mint btn--fullwidth">Go to session</button>
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

@Options({
  components: {
    Card,
    ModuleCount,
    SessionCode,
    AddItem,
  },
})
export default class SessionOverview extends Vue {
  newSession(): void {
    console.log('new Session clicked');
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
