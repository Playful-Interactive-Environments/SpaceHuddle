<template>
  <div
    class="join container container--fullheight container--centered-horizontal"
  >
    <main class="join__content">
      <h1 class="heading heading--big heading--white">
        Ready for <br />
        adventure?
      </h1>
      <p class="join__text">
        Just enter the code your moderator provided and you’re ready to go!
      </p>
      <form @submit="submit">
        <label>
          <input
            class="input input--centered input--fullwidth"
            name="sessionKey"
            v-model="sessionKey"
            placeholder="Enter Session PIN"
            type="text"
          />
          <form-error :errors="errors"></form-error>
        </label>
        <button class="btn btn--mint btn--fullwidth" @click="submit">
          Join session
        </button>
      </form>
    </main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Participant } from '@/services/participant-service';
import * as participantService from '@/services/participant-service';
import * as authService from '@/services/auth-service';
import FormError from '../../components/shared/atoms/FormError.vue';

@Options({
  components: {
    FormError,
  },
})
export default class ClientJoin extends Vue {
  sessionKey = '';
  errors: string[] = [];

  mounted(): void {
    const browserKeyLS = authService.getBrowserKey();
    if (browserKeyLS) this.connectToSession(browserKeyLS);
  }

  async submit(e: Event): Promise<void> {
    e.preventDefault();
    if (this.sessionKey.length > 0) {
      this.connectToSession();
    } else {
      this.addError('Please enter a code.');
      return;
    }
  }

  addError(newError: string): void {
    if (this.errors.find((error) => error === newError) !== undefined) return;
    this.errors.push(newError);
  }

  async connectToSession(browserKeyLS: string | null = null): Promise<void> {
    const participantData: Partial<Participant> | Participant = browserKeyLS
      ? await participantService.reconnect(browserKeyLS)
      : await participantService.connect(this.sessionKey);

    console.log('after connect', participantData);

    if (participantData.state === participantService.ConnectState.ACTIVE) {
      authService.setBrowserKey(participantData.browserKey as string);
      authService.setAccessToken(participantData.accessToken as string);
      await this.$router.push({
        name: 'client-overview',
      });
    } else {
      this.addError('Sorry, the provided code is invalid.');
    }
  }
}
</script>

<style lang="scss" scoped>
.join {
  padding-top: 8vh;
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('../../assets/illustrations/telescope.png');
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
