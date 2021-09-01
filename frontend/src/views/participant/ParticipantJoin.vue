<template>
  <div
    class="
      join
      container2 container2--fullheight container2--centered-horizontal
    "
  >
    <main class="join__content">
      <h1 class="heading heading--big heading--white">
        {{ $t('participant.view.join.header') }}
      </h1>
      <p class="join__text">
        {{ $t('participant.view.join.info') }}
      </p>
      <form @submit="submit">
        <label>
          <input
            class="input input--centered input--fullwidth"
            name="sessionKey"
            v-model="connectionKey"
            :placeholder="$t('participant.view.join.pinInfo')"
            type="text"
          />
        </label>
        <form-error :errors="errors"></form-error>
        <button class="btn btn--mint btn--fullwidth" @click="submit">
          {{ $t('participant.view.join.submit') }}
        </button>
      </form>
    </main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Participant, ConnectState } from '@/types/api/Participant';
import * as participantService from '@/services/participant-service';
import * as authService from '@/services/auth-service';
import FormError from '@/components/shared/atoms/FormError.vue';
import {
  getErrorMessage,
  addError,
  clearErrors,
} from '@/services/exception-service';

@Options({
  components: {
    FormError,
  },
})
export default class ParticipantJoin extends Vue {
  connectionKey = '';
  errors: string[] = [];

  mounted(): void {
    const browserKeyLS = authService.getBrowserKey();
    if (browserKeyLS) this.connectionKey = browserKeyLS;
  }

  async submit(e: Event): Promise<void> {
    clearErrors(this.errors);
    e.preventDefault();
    if (this.connectionKey.length > 0) {
      await this.connectToSession();
    } else {
      addError(this.errors, 'participant.join.noCode');
      return;
    }
  }

  async connectToSession(): Promise<void> {
    if (this.connectionKey.includes('.')) {
      participantService.reconnect(this.connectionKey).then(
        (queryResult) => {
          this.handleConnectionResult(queryResult);
        },
        (error) => {
          console.log(error.response);
          addError(this.errors, getErrorMessage(error));
        }
      );
    } else {
      participantService.connect(this.connectionKey).then(
        (queryResult) => {
          this.handleConnectionResult(queryResult);
        },
        (error) => {
          console.log(error.response);
          //addError(this.errors, 'participant.join.codeInvalid');
          addError(this.errors, getErrorMessage(error));
        }
      );
    }
  }

  handleConnectionResult(
    participantData: Partial<Participant> | Participant
  ): boolean {
    if (participantData.participant && participantData.token) {
      if (participantData.participant.state === ConnectState.ACTIVE) {
        authService.setBrowserKey(
          participantData.participant.browserKey as string
        );
        authService.setAccessTokenParticipant(
          participantData.token.accessToken as string
        );
        this.$router.push({
          name: 'participant-overview',
        });
        return true;
      }
    }
    return false;
  }
}
</script>

<style lang="scss" scoped>
.join {
  padding-top: 8vh;
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('~@/assets/illustrations/telescope.png');
  background-position: center;
  background-size: cover;

  &::after {
    position: fixed;
    content: '';
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
      180deg,
      #1d29487a 0%,
      #1d294850 30%,
      transparent 50%,
      transparent 100%
    );
    z-index: 0;
  }

  &__content {
    z-index: 1;
  }

  &__text {
    margin-bottom: 1rem;
  }
}
</style>
