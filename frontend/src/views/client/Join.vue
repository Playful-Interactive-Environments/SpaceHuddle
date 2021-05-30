<template>
  <layout-client :centered="true" class="join">
    <h1>Ready for adventure?</h1>
    <p>
      Just enter the code your moderator provided in the field below and you’re
      ready to go!
    </p>
    <form></form>
    <label>
      <input
        name="sessionCode"
        v-model="roomCode"
        placeholder="Enter Session PIN"
        type="text"
      />
      <form-error :errors="errors"></form-error>
    </label>
    <button class="btn btn--mint" @click="submit">Join session</button>
  </layout-client>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import FormError from '../../components/atoms/FormError';
import LayoutClient from '../../components/layout/LayoutClient';

@Options({
  components: {
    LayoutClient,
    FormError,
  },
})
export default class Join extends Vue {
  roomCode = '';
  errors: string[] = [];

  async submit(): Promise<void> {
    if (this.roomCode.length > 0) {
      // TODO: add session logic
      await this.$router.push({ name: 'module-overview' });
    } else {
      this.errors.push('Please enter a code.');
    }
  }
}
</script>

<style scoped>
.join {
  color: #fff;
  background-image: url('../../assets/illustrations/telescope.png');
  background-position: center;
  background-size: cover;
}
</style>
