<template>
  <div class="expand">
    <div
      role="button"
      class="expand__title"
      @click="isExpanded = !isExpanded"
    >
      <h2><slot name="title"></slot></h2>
      <div class="expand__icon">
        <div
          aria-label="arrow"
          class="expand__arrow"
          :class="{ expanded: isExpanded }"
        ></div>
      </div>
    </div>
    <transition
      name="expand"
      v-on:before-enter="beforeEnter"
      v-on:enter="enter"
      v-on:before-leave="beforeLeave"
      v-on:leave="leave"
    >
      <section
        class="expand__content"
        v-show="isExpanded"
      >
        <slot name="content"></slot>
      </section>
    </transition>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class Expand extends Vue {
  isExpanded = true;

  beforeEnter(el: HTMLElement): void {
    el.style.height = '0';
    el.style.opacity = '0';
  }

  enter(el: HTMLElement): void {
    el.style.height = el.scrollHeight + 'px';
    el.style.opacity = '1';
  }

  beforeLeave(el: HTMLElement): void {
    el.style.height = el.scrollHeight + 'px';
    el.style.opacity = '1';
  }

  leave(el: HTMLElement): void {
    el.style.height = '0';
    el.style.opacity = '0';
  }
}
</script>

<style lang="scss" scoped>
@import '~@/assets/styles/icons.scss';

.expand {
  &__title {
    display: flex;
    justify-content: space-between;
    cursor: pointer;
    padding: 1rem 0.5rem;

    h2 {
      font-weight: bold;
      font-variant: petite-caps;
      font-size: x-large;
    }
  }

  &__icon {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  &__arrow {
    @include icon-m('~@/assets/icons/arrow.svg');
    margin-right: 0.2rem;
    transition: transform 0.4s;
    transform-origin: center;

    &.expanded {
      transform: rotate(180deg);
    }
  }

  &__content {
    transform-origin: top center;
    transition: height 0.25s ease-out, opacity 0.1s;
  }
}
</style>
