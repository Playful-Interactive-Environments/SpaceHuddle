<template>
  <div class="menubar">
    <Logo class="menubar__logo" isParticipant="true" />
    <router-link to="/overview">
      <el-dropdown v-on:command="menuItemSelected($event)">
        <span class="el-dropdown-link">
          <font-awesome-icon icon="bars" />
        </span>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item command="publicScreen">
              <font-awesome-icon icon="desktop" />
            </el-dropdown-item>
            <el-dropdown-item command="join">
              <font-awesome-icon icon="sign-out-alt" />
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </router-link>
  </div>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';
import Logo from '@/components/shared/atoms/Logo.vue';
import * as sessionService from '@/services/session-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {
    Logo,
  },
})
export default class Menubar extends Vue {
  sessionId = '';

  mounted(): void {
    sessionService
      .getParticipantSession(EndpointAuthorisationType.PARTICIPANT)
      .then((queryResult) => {
        this.sessionId = queryResult.id;
      });
  }

  menuItemSelected(command: string): void {
    switch (command) {
      case 'publicScreen':
        window.open(
          this.$router.resolve(
            `/public-screen/${this.sessionId}/${EndpointAuthorisationType.PARTICIPANT}`
          ).href,
          '_blank'
        );
        break;
      case 'join':
        this.$router.push('/join');
        break;
    }
  }
}
</script>

<style lang="scss" scoped>
.el-dropdown-link {
  color: white;
}

.menubar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;

  &__logo {
    font-weight: var(--font-weight-bold);
    display: flex;
    align-items: center;
    width: 40vw;
    margin-bottom: -6px;
  }
}
</style>
