<template>
  <vue-final-modal
    v-model="showModal"
    classes="base-modal__container"
    content-class="base-modal__content"
    :click-to-close="false"
    @opened="$emit('opened')"
  >
    <font-awesome-icon
      v-if="closable"
      icon="times"
      role="button"
      class="icon base-modal__close"
      @click="$emit('update:showModal', false)"
    />
    <!--<img
      v-if="closable"
      src="@/assets/icons/svg/cancel.svg"
      alt="cancel-icon"
      role="button"
      class="icon icon--m btn--icon-only base-modal__close"
      @click="$emit('update:showModal', false)"
    />-->
    <slot />
  </vue-final-modal>
</template>

<script lang="ts">
import { Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

export default class ModalBase extends Vue {
  @Prop({ default: false }) showModal!: boolean;
  @Prop({ default: true }) closable!: boolean;
}
</script>

<style lang="scss" scoped>
.base-modal {
  // we need ::v-deep here to overwrite the container class from the vue-final-modal with custom styles
  // more details: https://v3.vue-final-modal.org/examples/stepByStep
  ::v-deep &__container {
    display: flex;
    justify-content: center;
    align-items: center;
  }

  ::v-deep &__content {
    display: flex;
    flex-direction: column;
    text-align: left;
    margin: 0 1rem;
    padding: 1.5rem 1.5rem;
    border-radius: var(--border-radius);
    background: #fff;
    width: 40vw;
  }

  &__close {
    align-self: flex-end;
    position: absolute;
  }
}
</style>
