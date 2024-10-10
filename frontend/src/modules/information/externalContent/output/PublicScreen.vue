<template>
  <div class="iframe-container" v-if="isValidSourceLink">
    <iframe
      id="iframe"
      title="ExternalSource"
      width="100%"
      height="100%"
      :src="sourceLink"
    >
    </iframe>
  </div>
  <p v-else-if="sourceLink">
    {{ $t('module.information.externalContent.moderatorConfig.invalid') }}
  </p>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import { Task } from '@/types/api/Task';
import * as taskService from '@/services/task-service';

@Options({
  components: {
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  ideas: Idea[] = [];
  contentHeight = 100;

  task!: Task;

  sourceLink = '';

  get portrait(): boolean {
    return this.contentHeight > 200;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  @Watch('taskId', { immediate: true })
  reloadTaskSettings(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(this.taskId, this.updateTask);
  }

  updateTask(task: Task): void {
    this.task = task;
    const module = this.task.modules.find(
      (module) => module.parameter.sourceLink
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
</style>
