<template>
  <div class="not-found">
    <h1 class="heading heading--regular heading--white">404 - Not found</h1>
    <p>
      Sorry, this page does either not exist, or you are not authorized to
      access it.
    </p>
    <br />
    <router-link to="/">
      <button class="btn btn--mint">Go to Homepage</button>
    </router-link>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';

@Options({
  components: {},
})
export default class NotFound extends Vue {}
</script>

<style lang="scss" scoped>
.not-found {
  display: flex;
  flex-direction: column;
  justify-content: center;
  min-height: 100vh;
  align-items: center;
  text-align: center;
  padding: 1rem;
  color: white;
  background-image: url('../../assets/illustrations/bg_without_telescope.png');
  background-size: cover;
}
</style>
