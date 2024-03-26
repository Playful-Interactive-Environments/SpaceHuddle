<template>
  <Gallery v-if="currentVisModule === 'gallery'" :task-id="this.taskId" />
  <CardShuffle
    v-if="currentVisModule === 'cardShuffle'"
    :task-id="this.taskId"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';

import Gallery from '@/modules/common/visualisation_master/organisms/gallery.vue';
import CardShuffle from '@/modules/common/visualisation_master/organisms/cardShuffle.vue';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import * as timerService from '@/services/timer-service';
import * as sessionService from '@/services/session-service';

@Options({
  components: {
    CardShuffle,
    IdeaCard,
    Gallery,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  task: Task | null = null;
  authHeaderTyp!: EndpointAuthorisationType;
  ideas: Idea[] = [];
  galleryIndex = 0;

  currentVisModule: null | string = null;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    ideaService.registerGetIdeasForTask(
      this.taskId,
      IdeaSortOrder.TIMESTAMP,
      null,
      this.updateIdeas,
      this.authHeaderTyp,
      10
    );
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      2 * 60
    );
  }

  updateTask(task: Task): void {
    this.task = task;
    const visModule = this.task.modules.filter(
      (module) => module.name === 'visualisation_master'
    )[0];
    this.currentVisModule = visModule.parameter.visModule.id;
  }

  updateIdeas(ideas: Idea[]): void {
    ideas = ideas.reverse();
    if (this.ideas.length == 0) {
      this.ideas = ideas;
    } else {
      const newIdees: Idea[] = ideas.filter(
        (idea) => !this.ideas.some((old) => old.id == idea.id)
      );
      this.ideas.splice(this.galleryIndex + 2, 0, ...newIdees);
      let deleteIndex = 0;
      while (deleteIndex > -1) {
        deleteIndex = this.ideas.findIndex(
          (old) => !ideas.some((idea) => idea.id == old.id)
        );
        if (deleteIndex > -1) {
          this.ideas.splice(deleteIndex, 1);
        }
      }
    }
  }

  galleryIndexChanged(newIndex: number): void {
    this.galleryIndex = newIndex;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateTask);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>

<style lang="scss" scoped>
.el-carousel::v-deep(.el-carousel__item) {
  display: flex;
  justify-content: center;
  align-items: center;
}

.public-idea {
  max-width: 20rem;
}

.gallery-item {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
  width: 20rem;
  max-height: 500px;
}

.el-carousel::v-deep(.el-carousel__mask) {
  background-color: unset;
}

.el-card {
  width: 100%;
  height: 100%;
}

.el-card::v-deep(.el-card__body) {
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.el-card::v-deep(.card__text) {
  flex-basis: auto;
  flex-grow: 1;
  flex-shrink: 1;
  text-align: inherit;

  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  gap: 0.5rem;
}

.themeSelectionButton {
  display: block;
  width: 100%;
  text-align: right;
}
</style>
