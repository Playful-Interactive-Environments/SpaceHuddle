<template>
  <div v-if="modelValue" class="overlay-container" @click="hide">
    <div class="overlay" @click="interact">
      <div class="media">
        <div class="media-content">{{ title }}</div>
        <div class="media-right" @click="hide">
          <font-awesome-icon icon="xmark" />
        </div>
      </div>
      <div class="scroll">
        <slot></slot>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

@Options({
  components: { FontAwesomeIcon },
  emits: ['update:modelValue'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class DrawerBottomOverlay extends Vue {
  @Prop({ default: false }) readonly modelValue!: boolean;
  @Prop({ default: '' }) readonly title!: string;

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
  height: 70vh;
  width: 100%;
  bottom: 0;
  z-index: 100;
  border-radius: var(--corner-radius) var(--corner-radius) 0 0;
  background-color: var(--color-background);
}

.scroll {
  height: calc(70vh - 4rem);
  overflow-y: auto;
}

.media {
  height: 2rem;
  font-weight: var(--font-weight-bold);
}
</style>
