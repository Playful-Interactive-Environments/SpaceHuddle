<template>
  <el-container
    class="public-screen"
    :key="componentLoadIndex"
    v-if="isModerator"
  >
    <el-header class="public-screen__header">
      <PublicHeader>
        <TaskTimeline
          v-if="topicId"
          :topic-id="topicId"
          :session-id="sessionId"
          :activeTaskId="taskId"
          :readonly="true"
          :authHeaderTyp="authHeaderTyp"
        ></TaskTimeline>
      </PublicHeader>
      <el-header class="public-screen__overview">
        <div class="public-screen__overview-left">
          <TaskInfo v-if="task" :taskId="taskId" :shortenDescription="false" />
        </div>
        <div class="public-screen__overview-right">
          <Timer v-if="task" :entity="task" />
          <span class="connection-key" v-if="session">
            <span class="session-info">
              {{ session.title }}
            </span>
            <h3>
              {{ $t('shared.view.publicScreen.connectionKey') }}
            </h3>
            {{ session.connectionKey }}
          </span>
        </div>
      </el-header>
    </el-header>
    <el-container class="public-screen__container">
      <el-main class="public-screen__main">
        <PublicScreenComponent
          v-if="task"
          :task-id="taskId"
          :key="componentLoadIndex"
          :authHeaderTyp="authHeaderTyp"
        />
      </el-main>
    </el-container>
  </el-container>
  <el-container v-else class="public-screen" :key="componentLoadIndex">
    <el-page-header
      :content="$t('shared.view.publicScreen.title')"
      :title="$t('general.back')"
      @back="$router.go(-1)"
    />
    <el-header class="public-screen__header">
      <TaskInfo v-if="task" :taskId="taskId" :shortenDescription="false" />
    </el-header>
    <el-container class="public-screen__container">
      <el-main class="public-screen__main">
        <PublicScreenComponent
          v-if="task"
          :task-id="taskId"
          :key="componentLoadIndex"
          :authHeaderTyp="authHeaderTyp"
        />
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
import TaskTimeline from '@/components/moderator/organisms/Timeline/TaskTimeline.vue';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

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
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
  componentLoadIndex = 0;
  componentLoadingState: ComponentLoadingState = ComponentLoadingState.NONE;

  task: Task | null = null;
  session: Session | null = null;

  TaskType = TaskType;
  TaskStates = TaskStates;

  readonly intervalTime = 10000;
  interval!: any;

  get isModerator(): boolean {
    return this.authHeaderTyp === EndpointAuthorisationType.MODERATOR;
  }

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

  getModuleName(task: Task): string[] {
    if (task && task.modules && task.modules.length > 0)
      return task.modules.map((module) => module.name);
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
    this.startInterval();
  }

  startInterval(): void {
    this.interval = setInterval(this.getPublicScreenModule, this.intervalTime);
  }

  unmounted(): void {
    clearInterval(this.interval);
    this.task = null;
  }

  @Watch('sessionId', { immediate: true })
  async onSessionIdChanged(): Promise<void> {
    sessionService
      .getById(this.sessionId, this.authHeaderTyp)
      .then((session) => {
        this.session = session;
      });

    this.getPublicScreenModule();
  }

  getPublicScreenModule(): void {
    sessionService
      .getPublicScreen(this.sessionId, this.authHeaderTyp)
      .then((queryResult) => {
        if (this.taskId !== queryResult?.id) {
          this.task = null;
          if (queryResult) {
            const taskType = TaskType[queryResult.taskType];
            if (this.$options.components) {
              getAsyncModule(
                ModuleComponentType.PUBLIC_SCREEN,
                taskType,
                this.getModuleName(queryResult)
              ).then((component) => {
                if (this.$options.components) {
                  this.componentLoadingState = ComponentLoadingState.SELECTED;
                  this.$options.components['PublicScreenComponent'] = component;
                  this.componentLoadIndex++;
                  this.task = queryResult;
                }
              });
            }
            if (taskType) {
              this.$nextTick(() => {
                setModuleStyles(taskType);
              });
            }
          }
        }
      });
  }
}
</script>

<style lang="scss" scoped>
.el-main {
  overflow: unset;
}

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

  &__main {
    overflow-x: auto;
    margin: 0 -5rem;
    padding: 0 5rem 1rem 5rem;
  }

  &__overview {
    display: flex;
    justify-content: space-between;
    color: var(--color-primary);

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
    margin: 0;
    padding: 0;
    padding-top: 5rem;
  }
}

.el-page-header {
  padding: 2rem 5rem;
  color: var(--color-primary);
  text-transform: uppercase;
}

@media screen and (max-width: 768px) {
  .el-page-header {
    padding: 1rem 2.5rem;
  }

  .public-screen {
    overflow: auto;
    &__header {
      padding: 1rem 2.5rem;
    }

    &__container {
      padding: 1rem 0;
    }

    &__main {
      padding: 0 2.5rem;
      margin: 0;
    }
  }
}

@media screen and (min-width: 769px), print {
}

.session-info {
  text-transform: uppercase;
  letter-spacing: 1px;
  color: var(--module-color);
  font-size: var(--font-size-small);
}
</style>
