<template>
  <div>
    <draggable
      v-model="ideas"
      id="ideas"
      item-key="id"
      class="layout__columns"
      @end="dragDone"
    >
      <template v-slot:item="{ element }">
        <IdeaCard
          :idea="element"
          :isDraggable="true"
          :canChangeState="false"
          :showState="false"
          @ideaDeleted="getIdeas()"
        />
      </template>
      <template v-slot:footer>
        <AddItem
          :text="$t('module.information.default.moderatorContent.add')"
          :is-column="true"
          @addNew="showSettings = true"
        />
      </template>
    </draggable>
    <IdeaSettings
      v-model:show-modal="showSettings"
      :taskId="taskId"
      :idea="addIdea"
      :title="$t('module.information.default.moderatorContent.settingsTitle')"
    />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as taskService from '@/services/task-service';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import IdeaSettings from '@/components/moderator/organisms/settings/IdeaSettings.vue';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import draggable from 'vuedraggable';
import AddItem from '@/components/moderator/atoms/AddItem.vue';

@Options({
  components: {
    AddItem,
    IdeaSettings,
    IdeaCard,
    draggable,
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue {
  @Prop() readonly taskId!: string;
  ideas: Idea[] = [];
  addIdea: any = {
    keywords: '',
    description: '',
    link: null,
    image: null, // the datebase64 url of created image
  };
  showSettings = false;
  readonly intervalTime = 10000;
  interval!: any;

  async mounted(): Promise<void> {
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    taskService.getTaskById(this.taskId).then(() => {
      this.getIdeas();
    });
  }

  @Watch('showSettings', { immediate: true })
  onShowSettingsChanged(): void {
    if (!this.showSettings) {
      this.addIdea.keywords = '';
      this.addIdea.description = '';
      this.addIdea.image = null;
      this.addIdea.link = null;
      this.getIdeas();
    } else {
      this.addIdea.order = this.ideas.length;
      this.addIdea.parameter = [];
    }
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      await ideaService
        .getIdeasForTask(
          this.taskId,
          IdeaSortOrder.ORDER,
          null,
          EndpointAuthorisationType.MODERATOR
        )
        .then((result) => {
          this.ideas = result;
        });
    }
  }

  /* eslint-disable @typescript-eslint/explicit-module-boundary-types*/
  async dragDone(): Promise<void> {
    this.ideas.forEach((idea, index) => {
      idea.order = index;
      ideaService.putIdea(idea.id, idea);
    });
  }
}
</script>

<style scoped></style>
