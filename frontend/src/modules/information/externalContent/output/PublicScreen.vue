<template>
  <div class="iframe-container" v-if="isValidSourceLink">
    <el-button @click="toggleFullscreen" class="fullscreenButton">
      <font-awesome-icon :icon="['fas', 'expand']" />
    </el-button>
    <iframe
      id="iframe"
      ref="iframe"
      title="ExternalSource"
      width="100%"
      height="100%"
      :src="sourceLink"
    ></iframe>
  </div>
  <p v-else-if="sourceLink">
    {{ $t('module.information.externalContent.moderatorConfig.invalid') }}
  </p>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import { Module } from '@/types/api/Module';

@Options({
  emits: ['update'],
})
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  task!: Task;
  sourceLink = '';

  mounted() {
    // Register task settings when component is mounted
    this.reloadTaskSettings();
  }

  toggleFullscreen(): void {
    const iframeElement = this.$refs.iframe as HTMLElement;
    if (iframeElement.requestFullscreen) {
      iframeElement.requestFullscreen();
    }
  }

  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    taskService.registerGetTaskById(this.taskId, this.updateTask);
  }

  updateTask(task: Task): void {
    this.task = task;
    const module = this.task.modules.find(
      (module: Module) => module.parameter.sourceLink
    );
    if (module) {
      this.sourceLink = module.parameter.sourceLink;
    }
  }

  get isValidSourceLink(): boolean {
    const base64Pattern =
      /^(data:application\/pdf;base64,[A-Za-z0-9+/]+={0,2})$/;

    return (
      base64Pattern.test(this.sourceLink) ||
      new RegExp(
        '^(https?:\\/\\/)' +
          '((([a-z0-9\\-]+\\.)+[a-z]{2,})|' +
          'localhost|' +
          '\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}\\.\\d{1,3}|' +
          '\\[([0-9a-f]{1,4}:){7}[0-9a-f]{1,4}\\])' +
          '(\\:\\d+)?(\\/[-a-z0-9%_.~+&:]*)*' +
          '(\\?[;&a-z0-9%_.~+=-]*)?' +
          '(\\#[-a-z0-9_]*)?$',
        'i'
      ).test(this.sourceLink)
    );
  }
}
</script>

<style lang="scss" scoped>
.iframe-container {
  position: relative;
  width: 100%;
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}

iframe {
  width: 100%;
  height: 100%;
  border: none;
}

.fullscreenButton {
  position: absolute;
  top: 0.5rem;
  right: 0.5rem;
}
</style>
