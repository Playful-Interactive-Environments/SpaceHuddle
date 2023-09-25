<template>
  <div v-if="modelValue" class="overlay-container" @click="hide">
    <div
      class="overlay"
      @click="interact"
      :style="{ '--header-height': `${headerHeight}px` }"
    >
      <div class="media" ref="header">
        <div v-if="$slots.header" class="media-content">
          <slot name="header"></slot>
        </div>
        <div class="media-content" v-else>{{ title }}</div>
        <div class="media-right close" @click="hide">
          <font-awesome-icon icon="xmark" />
        </div>
      </div>
      <div class="scroll" :class="{ contentWithFooter: !!$slots.footer }">
        <slot></slot>
      </div>
      <div v-if="$slots.footer" class="footer">
        <slot name="footer"></slot>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

@Options({
  components: { FontAwesomeIcon },
  emits: ['update:modelValue'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class DrawerBottomOverlay extends Vue {
  @Prop({ default: false }) readonly modelValue!: boolean;
  @Prop({ default: '' }) readonly title!: string;
  observer!: MutationObserver;
  headerHeight = 0;

  mounted(): void {
    //initialise observer in mounted as otherwise this references observer
    this.observer = new MutationObserver(this.domChanged);
  }

  unmounted(): void {
    if (this.observer) this.observer.disconnect();
  }

  @Watch('modelValue', { immediate: true })
  onVisibilityChanged(): void {
    if (this.modelValue) {
      setTimeout(() => {
        const dom = this.$refs.header as HTMLElement;
        if (dom) {
          this.headerHeight = dom.offsetHeight;
          this.observer.observe(dom, {
            childList: true,
            subtree: true,
            attributes: false,
            attributeOldValue: false,
            characterData: true,
            characterDataOldValue: false,
          });
        }
      }, 100);
    } else if (this.observer) {
      this.observer.disconnect();
    }
  }

  domChanged(): void {
    const dom = this.$refs.header as HTMLElement;
    if (dom) {
      this.headerHeight = dom.offsetHeight;
    }
  }

  hide(): void {
    this.$emit('update:modelValue', false);
  }

  interact(event: PointerEvent): void {
    event.stopPropagation();
  }
}
</script>

<style lang="scss" scoped>
.overlay-container {
  position: absolute;
  top: 0;
  width: 100%;
  height: 100%;
}

.overlay {
  padding: 1rem;
  position: absolute;
  height: 70%;
  width: 100%;
  bottom: 0;
  z-index: 100;
  border-radius: var(--corner-radius) var(--corner-radius) 0 0;

  .close {
    position: absolute;
    top: 0;
    right: 0;
    padding: 1rem;
  }
  background-color: var(--color-background);
}

.scroll {
  height: calc(100% - var(--header-height)); // calc(70vh - 4rem);
  overflow-y: auto;
}

.contentWithFooter {
  height: calc(100% - 7rem - var(--header-height));
}

.footer {
  height: 7rem;
  background-color: unset;
  padding: unset;
}

.media {
  //min-height: 2rem;
  font-weight: var(--font-weight-bold);
  margin-bottom: 1rem;
}
</style>
