<template>
  <el-container ref="container">
    <el-aside width="70vw" class="mapSpace">
      <IdeaMap
        v-if="sizeLoaded && module"
        :ideas="ideas"
        :parameter="module?.parameter"
        :canChangePosition="() => false"
        :calculate-size="false"
        v-model:selected-idea="selectedIdea"
        v-on:visibleIdeasChanged="visibleIdeasChanged"
        v-on:selectionColorChanged="selectionColor = $event"
      >
      </IdeaMap>
    </el-aside>
    <el-main>
      <section v-if="ideas.length === 0" class="centered public-screen__error">
        <p>{{ $t('module.brainstorming.map.publicScreen.noIdeas') }}</p>
      </section>
      <div v-else class="public-screen__content">
        <section class="layout__columns">
          <IdeaCard
            v-for="(idea, index) in visibleIdeas"
            :idea="idea"
            :key="index"
            :is-editable="false"
            :isSelected="idea.id === selectedIdea?.id"
            :selectionColor="selectionColor"
            v-model:collapseIdeas="filter.collapseIdeas"
            v-model:fadeIn="ideaTransform[idea.id]"
            v-on:click="selectedIdea = idea"
          />
        </section>
      </div>
    </el-main>
  </el-container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import * as ideaService from '@/services/idea-service';
import * as taskService from '@/services/task-service';
import { Prop, Watch } from 'vue-property-decorator';
import { Idea } from '@/types/api/Idea';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import {
  defaultFilterData,
  FilterData,
  getFilterForTask,
} from '@/components/moderator/molecules/IdeaFilter.vue';
import { Task } from '@/types/api/Task';
import * as cashService from '@/services/cash-service';
import IdeaMap from '@/components/shared/organisms/IdeaMap.vue';
import { Module } from '@/types/api/Module';
import { setHash } from '@/utils/url';
import { registerDomElement, unregisterDomElement } from '@/vunit';

@Options({
  components: {
    IdeaMap,
    IdeaCard,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  module: Module | null = null;
  ideas: Idea[] = [];
  ideaTransform: { [id: string]: boolean } = {};
  readonly newTimeSpan = 10000;
  filter: FilterData = { ...defaultFilterData };
  sizeLoaded = false;
  visibleIdeas: Idea[] = [];
  selectedIdea: Idea | null = null;
  selectionColor = '#0192d0';

  ideaCash!: cashService.SimplifiedCashEntry<Idea[]>;
  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.MODERATOR,
      30
    );
    this.ideaCash = ideaService.registerGetIdeasForTask(
      this.taskId,
      this.filter.orderType,
      null,
      this.updateIdeas,
      this.authHeaderTyp,
      20
    );
  }

  @Watch('selectedIdea', { immediate: true })
  onSelectedIdeaChanged(): void {
    if (this.selectedIdea) setHash(this.selectedIdea.id);
  }

  updateTask(task: Task): void {
    this.filter = getFilterForTask(task);
    this.ideaCash.parameter.urlParameter = ideaService.getIdeaListParameter(
      this.filter.orderType,
      null
    );
    if (task.modules.length === 1) this.module = task.modules[0];
    else {
      const module = task.modules.find((t) => t.name === 'map');
      this.module = module ?? null;
    }
  }

  updateIdeas(ideas: Idea[]): void {
    const currentDate = new Date();
    ideas = this.filter.orderAsc ? ideas : ideas.reverse();
    ideas = ideaService.filterIdeas(
      ideas,
      this.filter.stateFilter,
      this.filter.textFilter
    );
    this.ideas = ideas;

    this.ideaTransform = Object.assign(
      {},
      ...this.ideas.map((idea) => {
        const timeSpan =
          currentDate.getTime() - new Date(idea.timestamp).getTime();
        return { [idea.id]: timeSpan <= this.newTimeSpan };
      })
    );
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateTask);
    cashService.deregisterAllGet(this.updateIdeas);
  }

  domKey = '';
  mounted(): void {
    const dom = (this.$refs.container as any)?.$el as HTMLElement;
    this.domKey = registerDomElement(
      dom,
      () => {
        this.sizeLoaded = true;
      },
      2000
    );
  }

  unmounted(): void {
    this.deregisterAll();
    unregisterDomElement(this.domKey);
  }

  visibleIdeasChanged(ideas: Idea[]): void {
    this.visibleIdeas = ideas;
  }
}
</script>

<style lang="scss" scoped>
.public-screen__content {
  //display: grid;
  //grid-template-columns: 80% 20%;
}

.new {
  padding-left: 1rem;
}

.mapSpace {
  height: 100%;
  margin-right: 1rem;
}
</style>
