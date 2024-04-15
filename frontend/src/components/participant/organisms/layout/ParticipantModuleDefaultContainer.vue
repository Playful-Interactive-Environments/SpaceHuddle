<template>
  <el-container class="module-content" v-loading="showLoadingState">
    <el-header v-if="$slots.header" class="slotsHeader moduleHeader">
      <slot name="header"></slot>
      <TaskInfo
        :taskId="taskId"
        :modules="[module]"
        :is-participant="true"
        :shortenDescription="false"
        :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
        class="infoSection"
        :collapseDescription="true"
      />
    </el-header>
    <el-header v-else-if="$slots.headerAfterInfo" class="moduleHeader">
      <TaskInfo
        :taskId="taskId"
        :modules="[module]"
        :is-participant="true"
        :shortenDescription="false"
        :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
        :collapseDescription="true"
      />
      <slot name="headerAfterInfo"></slot>
    </el-header>
    <el-header
      v-else-if="$slots.headerOverlay"
      class="slotsHeaderOverlay moduleHeader"
    >
      <TaskInfo
        :taskId="taskId"
        :modules="[module]"
        :is-participant="true"
        :shortenDescription="false"
        :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
        class="infoSectionOverlay"
        :collapseDescription="true"
      />
      <slot name="headerOverlay"></slot>
    </el-header>
    <el-header v-else class="moduleHeader">
      <TaskInfo
        :taskId="taskId"
        :modules="[module]"
        :is-participant="true"
        :shortenDescription="false"
        :auth-header-typ="EndpointAuthorisationType.PARTICIPANT"
        :collapseDescription="true"
      />
    </el-header>
    <el-main
      class="el-main__overflow participant-content"
      v-if="useScrollContent"
    >
      <div ref="scrollContent">
        <el-scrollbar
          v-if="scrollSizeCalculated"
          always
          :height="`${scrollHeight}px`"
          :style="{ '--scroll-height': `${scrollHeight}px` }"
        >
          <slot />
        </el-scrollbar>
      </div>
    </el-main>
    <el-main class="el-main__overflow participant-content" v-else>
      <slot />
    </el-main>
    <el-footer v-if="!!$slots.footer" class="fixed">
      <slot name="footer"></slot>
    </el-footer>
  </el-container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import TaskInfo from '@/components/shared/molecules/TaskInfo.vue';
import { Prop } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import { registerDomElement, unregisterDomElement } from '@/vunit';

@Options({
  components: {
    TaskInfo,
  },
})
export default class ParticipantModuleDefaultContainer extends Vue {
  @Prop({ required: true }) taskId!: string;
  @Prop({ default: 'default' }) module!: string;
  @Prop({ default: false }) useScrollContent!: boolean;
  @Prop({ default: 0 }) scrollOverlay!: number;
  @Prop({ default: false }) readonly showLoadingState!: boolean;

  EndpointAuthorisationType = EndpointAuthorisationType;
  scrollHeight = 100;
  scrollSizeCalculated = false;

  domKey = '';
  mounted(): void {
    if (this.useScrollContent) {
      this.domKey = registerDomElement(
        this.$refs.scrollContent as HTMLElement,
        (targetWidth, targetHeight) => {
          this.scrollHeight = targetHeight + this.scrollOverlay;
          this.scrollSizeCalculated = true;
        },
        2000,
        false,
        () => {
          this.scrollHeight = 100;
        }
      );
    }
  }

  unmounted(): void {
    unregisterDomElement(this.domKey);
  }
}
</script>

<style lang="scss" scoped>
.slotsHeader {
  max-width: inherit;
  margin: -1rem -2rem 0 -2rem;
  left: 0;
  right: 0;
  z-index: 0;
}

.infoSection {
  max-width: inherit;
  margin-top: -1.5rem;
  border-radius: 30px 30px 0 0;
  background-color: white;
  padding: 1rem 2rem;
}

.slotsHeaderOverlay {
  position: relative;
  max-width: inherit;
  left: 0;
  right: 0;
  z-index: 0;
}

.infoSectionOverlay {
  max-width: inherit;
}

.right {
  position: absolute;
  top: 1rem;
  right: 2rem;
}

.module-content {
  background-color: var(--color-background);
  color: var(--color-dark-contrast);
  flex-grow: 1;
  justify-content: space-between;
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  margin: 0;
  padding: 1rem 2rem;
  max-width: inherit;
  position: relative;
}

.el-affix::v-deep(.el-affix--fixed) {
  background-color: white;
  padding: 1rem 0;
  margin-bottom: -1.3rem;
}

.el-affix::v-deep(.el-form) {
  margin-bottom: 2.3rem;
}

.el-affix::v-deep(.el-form-item--default) {
  margin-bottom: 0;
}

.el-affix::v-deep(.media) {
  flex-direction: row;
  width: 100%;
}

.fixed {
  position: sticky;
  background-color: var(--color-background);
  margin-bottom: -1rem;
  padding: 1rem 0;
}
</style>
