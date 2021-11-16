<template>
  <el-container class="public-screen" :key="componentLoadIndex">
    <el-header class="public-screen__header">
      <PublicHeader>
        <TaskTimeline
          v-if="topicId"
          :topic-id="topicId"
          :session-id="sessionId"
          :activeTaskId="taskId"
          :readonly="true"
        ></TaskTimeline>
      </PublicHeader>
      <el-header class="public-screen__overview">
        <div class="public-screen__overview-left">
          <TaskInfo
            v-if="task"
            :type="TaskType[task.taskType]"
            :title="task.name"
            :description="task.description"
          />
        </div>
        <div class="public-screen__overview-right">
          <Timer v-if="task" :task="task" />
          <span class="connection-key" v-if="session">
            <h3>
              {{ $t('shared.view.publicScreen.connectionKey') }}
            </h3>
            {{ session.connectionKey }}
          </span>
          <!--<img
            v-if="task"
            :src="
              require(`@/assets/illustrations/planets/${
                TaskType[task.taskType]
              }.png`)
            "
            alt="planet"
            class="public-screen__overview-planet"
          />-->
        </div>
      </el-header>
    </el-header>
    <el-container class="public-screen__container">
      <el-main>
        <PublicScreenComponent :task-id="taskId" :key="componentLoadIndex" />
      </el-main>
    </el-container>
  </el-container>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

import PublicHeader from '@/components/moderator/organisms/layout/PublicHeader.vue';
import Timer from '@/components/shared/atoms/Timer.vue';

import * as sessionService from '@/services/session-service';
import { Task } from '@/types/api/Task';
import { Session } from '@/types/api/Session';
import TaskType from '@/types/enum/TaskType';

import { setModuleStyles } from '@/utils/moduleStyles';
import TaskStates from '@/types/enum/TaskStates';
import {
  getAsyncDefaultModule,
  getAsyncModule,
  getEmptyComponent,
} from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import TaskInfo from '@/components/shared/molecules/TaskInfo.vue';
import { ComponentLoadingState } from '@/types/enum/ComponentLoadingState';
import TaskTimeline from '@/components/moderator/organisms/TaskTimeline.vue';

@Options({
  components: {
    TaskTimeline,
    PublicHeader,
    Timer,
    TaskInfo,
    PublicScreenComponent: getEmptyComponent(),
  },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly sessionId!: string;
  componentLoadIndex = 0;
  componentLoadingState: ComponentLoadingState = ComponentLoadingState.NONE;

  task: Task | null = null;
  session: Session | null = null;

  TaskType = TaskType;
  TaskStates = TaskStates;

  readonly intervalTime = 10000;
  interval!: any;

  get taskId(): string | null {
    if (this.task) return this.task.id;
    return null;
  }

  get topicId(): string | null {
    if (this.task) return this.task.topicId;
    return null;
  }

  get taskType(): TaskType | null {
    if (this.task) return TaskType[this.task.taskType];
    return null;
  }

  get moduleName(): string[] {
    if (this.task && this.task.modules && this.task.modules.length > 0)
      return this.task.modules.map((module) => module.name);
    return ['default'];
  }

  async mounted(): Promise<void> {
    getAsyncDefaultModule(ModuleComponentType.PUBLIC_SCREEN).then(
      (component) => {
        if (
          this.$options.components &&
          this.componentLoadIndex == 0 &&
          this.componentLoadingState == ComponentLoadingState.NONE
        ) {
          this.componentLoadingState = ComponentLoadingState.DEFAULT;
          this.$options.components['PublicScreenComponent'] = component;
          this.componentLoadIndex++;
        }
      }
    );
    this.startIdeaInterval();
  }

  startIdeaInterval(): void {
    this.interval = setInterval(this.getPublicScreenModule, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
  }

  @Watch('sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    sessionService.getById(this.sessionId).then((session) => {
      this.session = session;
    });

    this.getPublicScreenModule();
  }

  getPublicScreenModule(): void {
    sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
      if (this.taskId != queryResult?.id) {
        this.task = queryResult;
        const taskType = this.taskType;
        if (this.$options.components) {
          getAsyncModule(
            ModuleComponentType.PUBLIC_SCREEN,
            taskType,
            this.moduleName
          ).then((component) => {
            if (this.$options.components) {
              this.componentLoadingState = ComponentLoadingState.SELECTED;
              this.$options.components['PublicScreenComponent'] = component;
              this.componentLoadIndex++;
            }
          });
        }
        if (taskType) {
          this.$nextTick(() => {
            setModuleStyles(taskType);
          });
        }
      }
    });
  }
}
</script>

<style lang="scss" scoped>
.media::v-deep {
  .logo {
    padding: 0;
  }
}

h3 {
  font-weight: var(--font-weight-bold);
}

.connection-key {
  font-size: 1.5rem;
  padding: 1rem 0 1rem 3rem;
  text-align: right;
}

.module-info::v-deep {
  flex-grow: unset;

  h3 {
    font-size: 1.5rem;
  }
}

.public-screen {
  //background: url('~@/assets/illustrations/stars-background-repeat.png');
  background-color: var(--color-background-gray);
  background-size: contain;
  min-height: 100vh;

  &__header {
    position: sticky;
    top: 0;
    background-color: var(--color-background-gray);
    padding: 1rem 5rem;
    z-index: 1000;
  }

  &__container {
    padding-top: 0;
    padding: 1rem 5rem;
    height: 100%;
  }

  &__overview {
    display: flex;
    justify-content: space-between;
    color: var(--color-primary); // white;

    &-planet {
      height: 9rem;
      margin-left: 2rem;
      margin-top: -20px;
      margin-right: -20px;

      background-image: url('~@/assets/illustrations/bg_without_telescope.png');
      background-position: center top;
      background-size: cover;
      border-radius: 50%;
      border: 5px double white;
      padding: 1rem;
    }

    &-left {
      display: flex;
      justify-content: center;
      flex-direction: column;
      max-width: 65%;
    }

    &-right {
      display: flex;
      align-items: center;
    }

    &-type {
      text-transform: uppercase;
      letter-spacing: 1px;
      color: var(--module-color);
    }
  }

  &__content {
    margin-top: 1em;
  }

  &__error {
    color: var(--color-primary); // white;
    margin: 0px;
    padding: 0px;
    padding-top: 5rem;
  }
}
</style>
