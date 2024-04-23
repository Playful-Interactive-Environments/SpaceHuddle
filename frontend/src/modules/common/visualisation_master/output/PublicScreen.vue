<template>
  <div class="timeControls" v-if="displayTimeMod">
    <el-button class="playPause">
      <font-awesome-icon
        v-if="paused"
        :icon="['fas', 'play']"
        @click="paused = false"
      />
      <font-awesome-icon
        v-else
        :icon="['fas', 'pause']"
        @click="paused = true"
      />
    </el-button>
    <el-select class="timeModSelect" v-model="timeModifier">
      <template v-slot:prefix>
        <font-awesome-icon icon="sort" class="el-icon" />
      </template>
      <el-option
        v-for="type in timeModifierArray"
        :key="type"
        :value="type"
        :label="'x' + type"
      >
        <span>
          {{ 'x' + type }}
        </span>
      </el-option>
    </el-select>
  </div>
  <div id="visContainer">
    <Gallery
      v-if="currentVisModule === 'gallery'"
      :task-id="this.taskId"
      :timeModifier="timeModifier"
      :ideas="this.ideas"
      :paused="paused"
    />
    <CardShuffle
      v-if="currentVisModule === 'cardShuffle'"
      :task-id="this.taskId"
      :timeModifier="timeModifier"
      :ideas="this.ideas"
      :paused="paused"
    />
    <infinite-scroll
      v-if="currentVisModule === 'infiniteScroll'"
      :task-id="this.taskId"
      :timeModifier="timeModifier"
      :ideas="this.ideas"
      :paused="paused"
    />
    <BarChart
      v-if="currentVisModule === 'barChart'"
      :task-id="this.taskId"
      :timeModifier="timeModifier"
      :timerEnded="this.timerEnd"
    />
    <strata
      v-if="currentVisModule === 'strata'"
      :task-id="this.taskId"
      :timeModifier="timeModifier"
      :timerEnded="this.timerEnd"
    />
  </div>
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
import BarChart from '@/modules/common/visualisation_master/organisms/barChart.vue';
import moduleConfig from '@/modules/common/visualisation_master/data/moduleConfig.json';
import InfiniteScroll from '@/modules/common/visualisation_master/organisms/infiniteScroll.vue';
import Strata from '@/modules/common/visualisation_master/organisms/strata.vue';

@Options({
  components: {
    Strata,
    InfiniteScroll,
    BarChart,
    CardShuffle,
    IdeaCard,
    Gallery,
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: false }) readonly timerEnd!: boolean;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  task: Task | null = null;
  authHeaderTyp!: EndpointAuthorisationType;
  allIdeas: Idea[] = [];
  ideas: Idea[] = [];

  paused = false;

  taskType: null | string = null;
  currentVisModule: null | string = null;

  timeModifier: number | null = 1;
  timeModifierArray: number[] = [0.25, 0.5, 0.75, 1, 1.5, 2, 4];

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(): void {
    this.deregisterAll();
    if (
      this.currentVisModule &&
      moduleConfig.visModules[this.currentVisModule].type === 'IDEA'
    ) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        this.task ? this.task.parameter.orderType : IdeaSortOrder.TIMESTAMP,
        null,
        this.updateIdeas,
        this.authHeaderTyp,
        10
      );
    }
    taskService.registerGetTaskById(
      this.taskId,
      this.updateTask,
      EndpointAuthorisationType.PARTICIPANT,
      2 * 60
    );
  }

  @Watch('currentVisModule', { immediate: true })
  onCurrentVisModuleChanged(): void {
    if (
      this.currentVisModule &&
      moduleConfig.visModules[this.currentVisModule].type === 'IDEA'
    ) {
      ideaService.registerGetIdeasForTask(
        this.taskId,
        this.task ? this.task.parameter.orderType : IdeaSortOrder.TIMESTAMP,
        null,
        this.updateIdeas,
        this.authHeaderTyp,
        10
      );
    }
  }

  get displayTimeMod(): boolean {
    if (this.currentVisModule) {
      return moduleConfig.visModules[this.currentVisModule].allowTimeMod;
    } else {
      return false;
    }
  }
  updateTask(task: Task): void {
    this.task = task;
    const visModule = this.task.modules.filter(
      (module) => module.name === 'visualisation_master'
    )[0];
    this.currentVisModule = visModule.parameter.visModule.id;
  }

  @Watch('task.parameter.stateFilter', { immediate: true })
  onStateFilterChanged(): void {
    this.ideas = this.allIdeas.filter((d) =>
      this.task?.parameter.stateFilter.includes(d.state)
    );
  }

  updateIdeas(ideas: Idea[]): void {
    this.allIdeas = ideas;
    if (this.task && this.task.parameter.stateFilter) {
      ideas = ideas.filter((d) =>
        this.task?.parameter.stateFilter.includes(d.state)
      );
    }
    this.ideas = ideas;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateIdeas);
    cashService.deregisterAllGet(this.updateTask);
  }

  unmounted(): void {
    this.deregisterAll();
  }

  menuItemSelected(command: string): void {
    switch (command) {
      case 'edit':
        break;
      case 'delete':
        break;
    }
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

.timeControls {
  width: 100%;
  position: absolute;
  top: 1.5rem;
  left: 0;
  padding-left: 3rem;
  padding-right: 3rem;
  display: flex;
  flex-direction: row;
  justify-content: right;
  align-items: center;
}

.timeModSelect {
  width: 8em;
  z-index: 10000;
}

#visContainer {
  height: 87%;
}

.playPause {
  z-index: 1000;
  background-color: transparent;
  margin: 0;
  color: var(--color-dark-contrast);
  font-size: var(--font-size-xxlarge);
}
</style>
