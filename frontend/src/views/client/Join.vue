<template>
  <div class="join container container--fullheight container--centered">
    <h1>Ready for adventure?</h1>
    <p>
      Just enter the code your moderator provided in the field below and you’re
      ready to go!
    </p>
    <form @submit="submit">
      <label>
        <input
          name="sessionCode"
          v-model="roomCode"
          placeholder="Enter Session PIN"
          type="text"
        />
        <form-error :errors="errors"></form-error>
      </label>
      <button class="btn btn--mint" type="submit">Join session</button>
    </form>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import FormError from '../../components/shared/atoms/FormError.vue';

@Options({
  components: {
    FormError,
  },
})
export default class Join extends Vue {
  roomCode = '';
  errors: string[] = [];

  async submit(e: Event): Promise<void> {
    if (this.roomCode.length > 0) {
      // TODO: add session logic
      await this.$router.push({ name: 'module-overview' });
    } else {
      // TODO: prevent infinitely adding error codes - use vuelidate?
      this.errors.push('Please enter a code.');
    }
    e.preventDefault();
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
