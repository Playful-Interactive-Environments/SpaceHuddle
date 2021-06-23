<template>
  <div class="toggle">
    <div>{{ label }}</div>

    <label class="toggle__switch">
      <input type="checkbox" class="input toggle__input" />
      <span class="toggle__slider"></span>
    </label>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class Toggle extends Vue {
  @Prop({ default: '' }) label!: string;
}
</script>

<style lang="scss" scoped>
.toggle {
  display: flex;
  justify-content: space-between;
  align-items: center;

  + .toggle {
    margin-top: 0.5rem;
  }

  &__input {
    width: 0;
    height: 0;
    opacity: 0;

    &:checked + .toggle__slider {
      background-color: var(--color-mint);
      background-color: var(--module-color);
    }

    &:checked + .toggle__slider:before {
      -webkit-transform: translateX(26px);
      -ms-transform: translateX(26px);
      transform: translateX(26px);
    }
  }

  &__switch {
    position: relative;
    display: inline-block;
    width: calc(56rem / 16);
    height: calc(30rem / 16);
  }

  &__slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: var(--color-gray);
    -webkit-transition: 0.4s;
    transition: 0.4s;
    border-radius: calc(34rem / 16);

    &::before {
      position: absolute;
      content: '';
      height: calc(22rem / 16);
      width: calc(22rem / 16);
      left: calc(4rem / 16);
      bottom: calc(4rem / 16);
      background-color: white;
      -webkit-transition: 0.4s;
      transition: 0.4s;
      border-radius: 50%;
    }
  }
}
</style>
