<template>
  <div class="session-code">
    <button
      class="btn btn--fullwidth btn--icon"
      :class="[hasBorder ? 'btn--outline-gray' : 'btn--white']"
      @click="copyToClipboard"
    >
      <img
        src="@/assets/icons/copy.svg"
        alt="copy-icon"
        width="20"
        height="auto"
      />
      {{ code }}
    </button>
    <button
      class="btn btn--icon session-code__share"
      :class="[hasBorder ? 'btn--outline-gray' : 'btn--white']"
    >
      <img
        src="@/assets/icons/share.svg"
        alt="share-icon"
        width="20"
        height="auto"
      />
    </button>
  </div>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import SnackbarType from '@/types/enum/SnackbarType';
import { EventType } from '@/types/enum/EventType';

@Options({
  components: {},
})
export default class ModuleCount extends Vue {
  @Prop({ default: false }) hasBorder!: boolean;
  @Prop({ default: '' }) code!: string;

  copyToClipboard(): void {
    navigator.clipboard.writeText(this.code).then(
      () => {
        this.eventBus.emit(EventType.SHOW_SNACKBAR, {
          type: SnackbarType.SUCCESS,
          message: 'info.copiedToClipboard',
        });
      },
      () => {
        this.eventBus.emit(EventType.SHOW_SNACKBAR, {
          type: SnackbarType.ERROR,
          message: 'error.gui.copiedToClipboard',
        });
      }
    );
  }
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
