<template>
  <div
    class="join container container--fullheight container--centered-horizontal"
  >
    <h1>Ready for adventure?</h1>
    <p>
      Just enter the code your moderator provided in the field below and you’re
      ready to go!
    </p>
    <form @submit="submit">
      <label>
        <input
          class="input input--centered"
          name="sessionKey"
          v-model="sessionKey"
          placeholder="Enter Session PIN"
          type="text"
        />
        <form-error :errors="errors"></form-error>
      </label>
      <button class="btn btn--mint" @click="submit">Join session</button>
    </form>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import FormError from '../../components/shared/atoms/FormError.vue';
import * as participantService from '@/services/client/participant-service';

@Options({
  components: {
    FormError,
  },
})
export default class ClientJoin extends Vue {
  sessionKey = '';
  errors: string[] = [];

  async submit(e: Event): Promise<void> {
    e.preventDefault();
    if (this.sessionKey.length > 0) {
      const { state } = await participantService.connect(this.sessionKey);
      if (state === participantService.ConnectState.ACTIVE) {
        await this.$router.push({
          name: 'client-overview',
          params: {
            sessionKey: this.sessionKey,
          },
        });
      } else if (state === participantService.ConnectState.FAILED) {
        this.addError('Sorry, the provided code is invalid.');
      }
    } else {
      // TODO: use vuelidate or another validation library?
      this.addError('Please enter a code.');
    }
  }

  addError(newError: string): void {
    if (this.errors.find((error) => error === newError) !== undefined) return;
    this.errors.push(newError);
  }
}
</script>

<style scoped>
.join {
  padding-top: 3rem;
  color: #fff;
  background: var(--color-darkblue);
  background-image: url('../../assets/illustrations/telescope.png');
  background-position: center;
  background-size: cover;
}
</style>
