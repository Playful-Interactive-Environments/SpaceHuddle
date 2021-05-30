<template>
  <div class="layout-client" :class="{ 'layout-client--centered': centered }">
    <slot />
  </div>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

export default class LayoutClient extends Vue {
  @Prop({ default: 'default value' }) readonly centered!: boolean;
}
</script>

<style lang="scss" scoped>
.layout-client {
  display: flex;
  flex-direction: column;
  padding: var(--spacing--xl) var(--spacing--l);
  min-height: 100vh;
  background: var(--color-darkblue);

  &--centered {
    align-items: center;
    text-align: center;
  }
}
</style>
