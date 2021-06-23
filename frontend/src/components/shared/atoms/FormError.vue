<template>
  <ul class="form-error" :class="{ 'form-error--small': isSmall }">
    <li v-for="error in errors" class="form-error__message" :key="error">
      {{ typeof error === 'string' ? error : error.$message }}
    </li>
  </ul>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Vue } from 'vue-class-component';
import { ErrorObject } from '@vuelidate/core';

export default class FormError extends Vue {
  @Prop() errors!: string[] | ErrorObject[];
  @Prop({ default: false }) isSmall?: boolean;
}
</script>

<style lang="scss" scoped>
.form-error {
  &__message {
    color: var(--color-red);
  }

  &--small {
    font-size: var(--font-size-small);
  }
}
</style>
