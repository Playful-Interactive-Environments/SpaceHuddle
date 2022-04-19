<template>
  <el-container class="no-public-screen" v-if="isModerator && task === null">
    <el-header>
      <PublicHeader class="public-header">
        {{ $t('shared.view.publicScreen.connectTo') }}
        {{ joinLink }}
      </PublicHeader>
    </el-header>
    <el-main>
      <div class="media" v-if="session">
        <div class="media-content">
          <h1>
            {{ session.title }}
          </h1>
          <span>
            {{ session.description }}
          </span>
        </div>
        <div class="media-right">
          {{ session.connectionKey }}
          <QrcodeVue
            foreground="#1d2948"
            background="#f4f4f4"
            render-as="svg"
            :value="joinLink"
            :size="200"
            level="H"
          />
        </div>
      </div>
    </el-main>
    <el-footer>
      <img src="@/assets/illustrations/telescope_corner.png" alt="telescope" />
    </el-footer>
  </el-container>
  <el-container
    class="public-screen"
    :key="componentLoadIndex"
    v-else-if="isModerator"
  >
    <el-header class="public-screen__header star-header">
      <PublicHeader class="public-header"> </PublicHeader>
      <TaskTimeline
        v-if="topicId"
        :topic-id="topicId"
        :session-id="sessionId"
        :activeTaskId="taskId"
        :readonly="true"
        :authHeaderTyp="authHeaderTyp"
        :darkMode="true"
      ></TaskTimeline>
      <!--<el-header class="public-screen__overview">
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
      </el-header>-->
    </el-header>
    <el-container class="public-screen__timer">
      <el-header>
        <TimerProgress class="timer-process" v-if="task" :entity="task" />
      </el-header>
    </el-container>
    <el-container class="public-screen__container">
      <el-main class="public-screen__main">
        <div ref="scrollContent"></div>
        <el-scrollbar
          native
          :height="`calc(100vh - ${topContentPosition}px - 0.1rem)`"
        >
          <h3 v-if="task" class="heading--regular twoLineText">
            {{ task.name }}
          </h3>
          <PublicScreenComponent
            v-if="task"
            :task-id="taskId"
            :key="componentLoadIndex"
            :authHeaderTyp="authHeaderTyp"
          />
        </el-scrollbar>
      </el-main>
    </el-container>
  </el-container>
  <el-container
    v-else
    class="public-screen participant"
    :key="componentLoadIndex"
  >
    <el-header class="public-screen__header">
      <el-page-header
        :content="$t('shared.view.publicScreen.title')"
        :title="$t('general.back')"
        @back="$router.go(-1)"
      />
      <TaskInfo
        v-if="task"
        :taskId="taskId"
        :shortenDescription="false"
        :showType="false"
      />
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
import QrcodeVue from 'qrcode.vue';
import TimerProgress from '@/components/shared/atoms/TimerProgress.vue';

@Options({
  components: {
    TimerProgress,
    TaskTimeline,
    PublicHeader,
    Timer,
    TaskInfo,
    QrcodeVue,
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

  topContentPosition = 250;

  loadTopPositions(): void {
    if (this.$refs.scrollContent) {
      this.topContentPosition = Math.floor(
        (this.$refs.scrollContent as HTMLElement).getBoundingClientRect().top
      );
    }
  }

  updated(): void {
    setTimeout(() => {
      this.loadTopPositions();
    }, 500);
  }

  get joinLink(): string {
    if (this.session)
      return `${window.location.origin}/join/${this.session.connectionKey}`;
    return `${window.location.origin}/join/`;
  }

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
    this.loadTopPositions();
    sessionService
      .getPublicScreen(this.sessionId, this.authHeaderTyp)
      .then(async (queryResult) => {
        if (this.taskId !== queryResult?.id) {
          let task: Task | null = null;
          if (queryResult) {
            const taskType = TaskType[queryResult.taskType];
            if (this.$options.components) {
              await getAsyncModule(
                ModuleComponentType.PUBLIC_SCREEN,
                taskType,
                this.getModuleName(queryResult)
              ).then((component) => {
                if (this.$options.components) {
                  this.componentLoadingState = ComponentLoadingState.SELECTED;
                  this.$options.components['PublicScreenComponent'] = component;
                  this.componentLoadIndex++;
                  task = queryResult;
                }
              });
            }
            if (taskType) {
              this.$nextTick(() => {
                setModuleStyles(taskType);
              });
            }
          }
          this.task = task;
        }
      });
  }
}
</script>

<style lang="scss" scoped>
.timer-process {
  position: relative;
  top: -0.8rem;
  padding: 0 calc(var(--corner-radius) + 2rem);
  z-index: 100;
}

.public-header {
  padding-top: 1rem;
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
  --corner-radius: 5rem;
  --side-padding: 3rem;

  &__header {
    position: sticky;
    top: 0;
    background-color: var(--color-background-gray);
    padding: 1rem var(--side-padding);
    z-index: 1000;

    &.star-header {
      background: var(--color-darkblue);
      background-image: url('~@/assets/illustrations/stars-background.png');
      background-size: contain;
      mask-image: radial-gradient(
          circle farthest-corner at 100% 100%,
          transparent 69%,
          white 70%
        ),
        radial-gradient(
          circle farthest-corner at 0% 100%,
          transparent 69%,
          white 70%
        ),
        linear-gradient(white, white);
      mask-size: var(--corner-radius) var(--corner-radius),
        var(--corner-radius) var(--corner-radius),
        100% calc(100% - var(--corner-radius) + 1px);
      mask-position: bottom left, bottom right, top left;
      mask-repeat: no-repeat;
      padding: 0 1rem calc(1rem + var(--corner-radius)) 1rem;
    }

    .el-main {
      overflow: unset;
    }

    .media::v-deep {
      .logo {
        padding: 0;
      }
    }
  }

  &__container {
    //margin-top: calc(-1 * var(--corner-radius));
    padding: 0;
    height: 100%;
  }

  &__timer {
    height: 0;
    z-index: 2000;
    margin-top: calc(-1 * var(--corner-radius));

    .el-header {
      height: 0;
    }
  }

  &__main {
    overflow-x: auto;
    scrollbar-color: var(--color-primary) var(--color-gray);
    scrollbar-width: thin;
    margin: 0;

    .el-scrollbar::v-deep {
      .el-scrollbar__wrap {
        padding: 0 var(--side-padding);
      }
      .el-scrollbar__view {
        padding-top: 2rem;
      }
    }
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

  &__error {
    color: var(--color-primary); // white;
    margin: 0;
    padding: 0;
    padding-top: var(--side-padding);
  }
}

h3 {
  padding-bottom: 1rem;
}

.participant {
  .public-screen__container {
    padding: 0 var(--side-padding);
  }

  h3 {
    padding-top: 1rem;
    padding-bottom: 0;
  }
}

.el-page-header {
  //padding: 2rem 5rem;
  color: var(--color-primary);
  text-transform: uppercase;
}

@media screen and (max-width: 768px) {
  .el-page-header {
    //padding: 1rem 2.5rem;
  }

  .public-screen {
    overflow: auto;
    scrollbar-color: var(--color-primary) var(--color-gray);
    scrollbar-width: thin;
    &__header {
      padding: 1rem 2.5rem;
    }

    &__container {
      padding: 0;
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

.no-public-screen {
  background-color: var(--color-background-gray);
  min-height: 100vh;
  --side-padding: 5rem;

  .el-header {
    position: fixed;
    width: 100vw;
    top: 0;
    left: 0;
    padding: 0 1rem;

    .public-header::v-deep {
      .media-content {
        text-align: right;
        margin-right: 2rem;
      }
    }
  }

  .el-main {
    padding: 1rem;

    .media {
      margin: var(--side-padding) var(--side-padding)
        calc(3 * var(--side-padding));

      h1 {
        font-size: var(--font-size-xxlarge);
        font-weight: var(--font-weight-semibold);
        line-height: 1.8;
      }

      .media-right {
        margin-left: var(--side-padding);
        font-size: 2.9rem;
        font-family: monospace;
        svg {
          display: flex;
        }
      }
    }
  }

  .el-footer {
    position: fixed;
    width: 100vw;
    bottom: 0;
    left: 0;
    line-height: 0;

    img {
      height: 30rem;
      max-height: 50vh;
      max-width: unset;
    }
  }
}

.process-timeline-container {
  max-width: calc(100vw - 2rem);
}
</style>
