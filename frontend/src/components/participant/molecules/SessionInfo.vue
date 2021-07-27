<template>
  <div class="session-info">
    <div class="session-info__title">{{ title }}</div>
    <div class="session-info__description">{{ description }}</div>
    <img src="@/assets/icons/info.svg" alt="info" class="session-info__icon" />
  </div>
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class SessionInfo extends Vue {
  @Prop({ default: '' }) title!: string;
  @Prop({ default: '' }) description!: string;
}
</script>

<style lang="scss" scoped>
.session-info {
  position: relative;
  background-color: rgba(255, 255, 255, 0.3);
  border-radius: var(--border-radius-small);
  padding: 1rem 1.4rem;
  color: white;

  &__title {
    font-weight: var(--font-weight-medium);
    margin-bottom: 0.4rem;
  }

  &__description {
    font-size: var(--font-size-small);
    max-width: 95%;
    opacity: 0.5;
  }

  &__icon {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 20px;
  }
}
</style>
