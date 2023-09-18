<template>
  <div class="session-code">
    <el-button
      :type="buttonType"
      class="fullwidth stretch"
      @click="copyToClipboard(code)"
    >
      <font-awesome-icon icon="clone" />
      <TutorialStep type="sessionDetails" step="connectionCode" :order="1">
        <span>{{ code }}</span>
      </TutorialStep>
    </el-button>
    <TutorialStep type="sessionDetails" step="shareCode" :order="2">
      <el-button
        :type="buttonType"
        v-if="hasSharing"
        class="session-code__share"
        v-on:click="copyToClipboard(connectionLink)"
      >
        <font-awesome-icon icon="share-alt" />
      </el-button>
    </TutorialStep>
  </div>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';
import { copyToClipboard } from '@/utils/date';

@Options({
  components: { TutorialStep },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SessionCode extends Vue {
  @Prop({ default: 'default' }) buttonType!: string;
  @Prop({ default: true }) hasSharing!: boolean;
  @Prop({ default: '' }) code!: string;

  get connectionLink(): string {
    return `${window.location.origin}/join/${this.code}`;
  }

  copyToClipboard(text: string): void {
    copyToClipboard(text, this.$t);
  }

  share(): void {
    const subject = (this as any).$t(
      'moderator.molecule.sessionCode.shareSubject'
    );
    const body = (this as any)
      .$t('moderator.molecule.sessionCode.shareBody')
      .replace('@KEY', this.code)
      .replace('@LINK', this.connectionLink)
      .replace('\n', '%0D%0A');
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
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
