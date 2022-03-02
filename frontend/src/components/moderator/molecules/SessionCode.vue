<template>
  <div class="session-code">
    <el-button
      :type="buttonType"
      class="fullwidth stretch"
      @click="copyToClipboard"
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
        v-on:click="share"
      >
        <font-awesome-icon icon="share-alt" />
      </el-button>
    </TutorialStep>
  </div>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { ElMessage } from 'element-plus';
import TutorialStep from '@/components/shared/atoms/TutorialStep.vue';

@Options({
  components: { TutorialStep },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class SessionCode extends Vue {
  @Prop({ default: 'default' }) buttonType!: string;
  @Prop({ default: true }) hasSharing!: boolean;
  @Prop({ default: '' }) code!: string;

  copyToClipboard(): void {
    navigator.clipboard.writeText(this.code).then(
      () => {
        ElMessage({
          message: (this as any).$t('info.copyToClipboard'),
          type: 'success',
          center: true,
          showClose: true,
        });
      },
      () => {
        ElMessage({
          message: (this as any).$t('error.gui.copyToClipboard'),
          type: 'error',
          center: true,
          showClose: true,
        });
      }
    );
  }

  share(): void {
    const subject = (this as any).$t(
      'moderator.molecule.sessionCode.shareSubject'
    );
    const body = (this as any)
      .$t('moderator.molecule.sessionCode.shareBody')
      .replace('@KEY', this.code)
      .replace('@LINK', `${window.location.origin}/join/${this.code}`)
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
