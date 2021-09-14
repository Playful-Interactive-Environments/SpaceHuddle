<template>
  <Expand>
    <template v-slot:title>
      {{ $t('module.selection.default.moderatorContent.selection') }}
    </template>
    <template v-slot:icons>
      <span class="icon" v-on:click="addSelectedToSelection()">
        <font-awesome-icon icon="plus" />
      </span>
      <span class="icon" v-on:click="removeSelectedFromSelection()">
        <font-awesome-icon icon="minus" />
      </span>
    </template>
    <template v-slot:content>
      <main class="selection__content">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in selection"
          :key="index"
          :is-selectable="true"
          :is-deletable="false"
          v-model:is-selected="ideasSelectionRemove[idea.id]"
        />
      </main>
    </template>
  </Expand>
  <header class="selection__header">
    <label for="orderType" class="heading heading--xs">{{
      $t('module.selection.default.moderatorContent.sortOrder')
    }}</label>
    <select
      v-model="orderType"
      id="orderType"
      class="select select--fullwidth"
      @change="getIdeas"
    >
      <option v-for="type in SortOrderOptions" :key="type" :value="type">
        {{ $t(`enum.ideaSortOrder.${IdeaSortOrder[type]}`) }}
      </option>
    </select>
  </header>
  <Expand v-for="(item, key) in orderGroupContent" :key="key">
    <template v-slot:title>{{ key.toUpperCase() }}</template>
    <template v-slot:content>
      <main class="selection__content">
        <IdeaCard
          :idea="idea"
          v-for="(idea, index) in item"
          :key="index"
          :is-deletable="false"
          :is-selectable="true"
          v-model:is-selected="ideasSelectionAdd[idea.id]"
        />
      </main>
    </template>
  </Expand>

  <!--<main class="brainstorming__content">
    <IdeaCard
      :idea="idea"
      v-for="(idea, index) in ideas"
      :key="index"
      @ideaDeleted="getIdeas"
    />
  </main>-->
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import * as ideaService from '@/services/idea-service';
import IdeaCard from '@/components/moderator/molecules/IdeaCard.vue';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';
import Expand from '@/components/shared/atoms/Expand.vue';
import * as taskService from '@/services/task-service';
import { Task } from '@/types/api/Task';
import * as selectionService from '@/services/selection-service';
import { EventType } from '@/types/enum/EventType';
import SnackbarType from '@/types/enum/SnackbarType';

@Options({
  components: {
    IdeaCard,
    Expand,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContentComponent extends Vue {
  @Prop() readonly taskId!: string;
  task: Task | null = null;
  ideas: Idea[] = [];
  selection: Idea[] = [];
  orderGroupContent: { [name: string]: Idea[] } = {};
  ideasSelectionAdd: { [name: string]: boolean } = {};
  ideasSelectionRemove: { [name: string]: boolean } = {};
  readonly intervalTime = 10000;
  interval!: any;
  orderType = this.SortOrderOptions[0];

  IdeaSortOrder = IdeaSortOrder;

  get SortOrderOptions(): Array<keyof typeof IdeaSortOrder> {
    return Object.keys(IdeaSortOrder) as Array<keyof typeof IdeaSortOrder>;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.getIdeas();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
    }
  }

  async getIdeas(): Promise<void> {
    if (this.taskId) {
      if (!this.task) await this.getTask();
      if (this.task) {
        await selectionService
          .getIdeasForSelection(this.task.parameter.selectionId)
          .then((ideas) => {
            this.selection = ideas;
            ideas.forEach((ideaItem) => {
              if (!this.ideasSelectionRemove[ideaItem.id])
                this.ideasSelectionRemove[ideaItem.id] = false;
            });
          });
        const selectedIds: string[] = this.selection.map((idea) => idea.id);
        await ideaService
          .getIdeasForTask(
            this.task.parameter.brainstormingTaskId,
            this.orderType
          )
          .then((ideas) => {
            this.orderGroupContent = {};
            this.ideas = ideas;
            ideas
              .filter((idea) => !selectedIds.includes(idea.id))
              .forEach((ideaItem) => {
                if (!this.ideasSelectionAdd[ideaItem.id])
                  this.ideasSelectionAdd[ideaItem.id] = false;
                if (ideaItem.order) {
                  const orderGroup = this.orderGroupContent[ideaItem.order];
                  if (!orderGroup) {
                    this.orderGroupContent[ideaItem.order] = [ideaItem];
                  } else {
                    orderGroup.push(ideaItem);
                  }
                }
              });
          });
      }
    }
  }

  async addSelectedToSelection(): Promise<void> {
    if (this.task && this.task.parameter && this.task.parameter.selectionId) {
      const selection: string[] = [];
      for (let [ideaId, isSelected] of Object.entries(this.ideasSelectionAdd)) {
        if (isSelected) {
          selection.push(ideaId);
          this.ideasSelectionAdd[ideaId] = false;
        }
      }
      if (selection.length > 0) {
        await selectionService
          .addIdeasToSelection(this.task.parameter.selectionId, selection)
          .then(() => {
            this.getIdeas();
          });
      } else {
        this.eventBus.emit(EventType.SHOW_SNACKBAR, {
          type: SnackbarType.ERROR,
          message: 'error.vuelidate.noSelection',
        });
      }
    }
  }

  async removeSelectedFromSelection(): Promise<void> {
    if (this.task && this.task.parameter && this.task.parameter.selectionId) {
      const selection: string[] = [];
      for (let [ideaId, isSelected] of Object.entries(this.ideasSelectionRemove)) {
        if (isSelected) {
          selection.push(ideaId);
          this.ideasSelectionRemove[ideaId] = false;
        }
      }
      if (selection.length > 0) {
        await selectionService
          .removeIdeasFromSelection(this.task.parameter.selectionId, selection)
          .then(() => {
            this.getIdeas();
          });
      } else {
        this.eventBus.emit(EventType.SHOW_SNACKBAR, {
          type: SnackbarType.ERROR,
          message: 'error.vuelidate.noSelection',
        });
      }
    }
  }

  async mounted(): Promise<void> {
    this.startIdeaInterval();

    this.eventBus.off(EventType.CHANGE_SETTINGS);
    this.eventBus.on(EventType.CHANGE_SETTINGS, async () => {
      await this.getTask();
      await this.getIdeas();
    });
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getIdeas, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }
}
</script>

<style lang="scss" scoped>
.selection {
  &__header {
    padding: 0.5rem 1rem;
    margin-bottom: 1rem;
    border-radius: var(--border-radius);
    background-color: var(--color-darkblue);
    width: 100%;
    display: table;

    > * {
      display: table-cell;
    }

    label {
      width: 20%;
    }

    .heading {
      color: white;
    }
  }

  &__content {
    width: 100%;
    column-width: 22vw;
    column-gap: 1rem;
  }
}

.column {
  margin: 0.5rem;
}

.icon {
  margin-right: 0.5em;
}
</style>
