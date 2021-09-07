<template>
  <router-link :to="route">
    <div class="back">
      <font-awesome-icon icon="chevron-left" class="back__icon" />
      <div class="back__text">{{ $t('general.back') }}</div>
    </div>
  </router-link>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class BackButton extends Vue {
  @Prop({ default: '' }) route!: string;
}
</script>

<style lang="scss" scoped>
.back {
  display: flex;
  align-items: center;

  &__icon {
    width: 1rem;
    height: 1rem;
    margin-right: 0.5rem;
  }
}
</style>
