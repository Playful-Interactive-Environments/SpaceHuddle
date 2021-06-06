<template>
  <div class="session-code">
    <button
      class="btn btn--fullwidth btn--icon"
      :class="[hasBorder ? 'btn--outline-gray' : 'btn--white']"
    >
      <img
        src="@/assets/icons/copy.svg"
        alt="rocket-icon"
        width="20"
        height="auto"
      />
      YXCVBNM
    </button>
    <button
      class="btn btn--icon session-code__share"
      :class="[hasBorder ? 'btn--outline-gray' : 'btn--white']"
    >
      <img
        src="@/assets/icons/share.svg"
        alt="rocket-icon"
        width="20"
        height="auto"
      />
    </button>
  </div>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';

@Options({
  components: {},
})
export default class ModuleCount extends Vue {
  @Prop({ default: false }) hasBorder!: boolean;
}
</script>

<style lang="scss" scoped>
.session-code {
  display: flex;
  justify-content: space-between;
  gap: 0.5rem;

  button {
    font-weight: var(--font-weight-bold);
  }

  &__share {
    width: 40.57px;
    min-width: 40.57px;
    height: 40.57px;
    border-radius: 50%;
  }
}
</style>
