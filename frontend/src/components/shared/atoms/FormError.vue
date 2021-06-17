<template>
  <ul class="form-error">
    <li v-for="error in errors" class="form-error__message" :key="error">
      {{ error }}
    </li>
  </ul>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Vue } from 'vue-class-component';

export default class FormErrors extends Vue {
  @Prop({ default: [] }) errors?: string[];
}
</script>

<style lang="scss" scoped>
.form-error {
  &__message {
    color: var(--color-red);
  }
}
</style>
