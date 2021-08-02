<template>
  <router-link :to="route">
    <div class="back">
      <div class="back__icon"></div>
      <div class="back__text">{{ $t("general.back") }}</div>
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
    mask-image: url('~@/assets/icons/arrow.svg');
    mask-repeat: no-repeat;
    mask-position: center;
    background-color: var(--color-darkblue);
    width: 1rem;
    height: 1rem;
    margin-right: 0.5rem;
    transform: rotate(90deg);
  }
}
</style>
