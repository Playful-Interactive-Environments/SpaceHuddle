<template>
  <div
    class="add"
    :class="{ 'add--column': isColumn }"
    @click="$emit('addNew')"
  >
    <font-awesome-icon icon="plus" />
    <span class="add__text">{{ text }}</span>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class SessionOverview extends Vue {
  @Prop({ default: 'Add' }) text!: string;
  @Prop({ default: false }) isColumn!: boolean;
}
</script>

<style lang="scss" scoped>
.add {
  background-color: unset;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: calc(55rem / 16);
  gap: 0.5rem;
  border: 2px dashed var(--color-darkblue-light);
  color: var(--color-darkblue-light);
  border-radius: var(--border-radius-xs);
  cursor: pointer;
  transition: background-color 0.2s, box-shadow 0.2s, border-color 0.2s;

  &:not(.add--column) {
    margin: 1.5rem 0 2rem 3rem;
  }

  &:hover {
    background-color: white;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
    border-color: transparent;
  }

  &__icon {
    width: 18px;
    height: auto;
  }

  &--column {
    flex-direction: column;
    min-height: calc(110rem / 16);
  }
}
</style>
