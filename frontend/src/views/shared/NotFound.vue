<template>
  <div class="not-found" :lang="$i18n.locale">
    <h1 class="heading heading--regular heading--white">
      {{ $t('shared.view.notFound.header') }}
    </h1>
    <p>
      {{ $t('shared.view.notFound.info') }}
    </p>
    <br />
    <router-link to="/">
      <el-button type="info">
        {{ $t('shared.view.notFound.submit') }}
      </el-button>
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
  min-height: var(--app-height);
  align-items: center;
  text-align: center;
  padding: 1rem;
  color: white;
  background-image: url('~@/assets/illustrations/bg_without_telescope.png');
  background-size: cover;
}
</style>
