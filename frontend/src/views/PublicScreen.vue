<template>
  <div class="public-screen">
    <PublicHeader :white="true" />
    <main class="public-screen__container">
      <section v-if="task" class="public-screen__overview">
        <div class="public-screen__overview-left">
          <TaskInfo
            :type="TaskType[task.taskType]"
            :title="task.name"
            :description="task.description"
          />
        </div>
        <div class="public-screen__overview-right">
          <span class="connection-key" v-if="session">
            <h3>
              {{ $t('shared.view.publicScreen.connectionKey') }}
            </h3>
            {{ session.connectionKey }}
          </span>
          <Timer :task="task" />
          <img
            :src="
              require(`@/assets/illustrations/planets/${
                TaskType[task.taskType]
              }.png`)
            "
            alt="planet"
            class="public-screen__overview-planet"
          />
        </div>
      </section>
      <PublicScreenComponent :task-id="taskId" :key="componentLoadIndex" />
    </main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

import PublicHeader from '@/components/moderator/organisms/layout/PublicHeader.vue';
import Timer from '@/components/shared/atoms/Timer.vue';

import * as sessionService from '@/services/session-service';
import { Task } from '@/types/api/Task';
import { Session } from '@/types/api/Session';
import TaskType from '@/types/enum/TaskType';

import { setModuleStyles } from '@/utils/moduleStyles';
import TaskStates from '@/types/enum/TaskStates';
import {
  getAsyncModule,
  getAsyncDefaultModule,
  getEmptyComponent,
} from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import TaskInfo from '@/components/shared/molecules/TaskInfo.vue';

@Options({
  components: {
    PublicHeader,
    Timer,
    TaskInfo,
    PublicScreenComponent: getEmptyComponent(),
  },
})
export default class PublicScreen extends Vue {
  @Prop() readonly sessionId!: string;
  componentLoadIndex = 0;

  task: Task | null = null;
  session: Session | null = null;

  TaskType = TaskType;
  TaskStates = TaskStates;

  get taskId(): string | null {
    if (this.task) return this.task.id;
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
        if (this.$options.components && this.componentLoadIndex == 0) {
          this.$options.components['PublicScreenComponent'] = component;
          this.componentLoadIndex++;
        }
      }
    );

    sessionService.getById(this.sessionId).then((session) => {
      this.session = session;
    });

    sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
      this.task = queryResult;
      const taskType = this.taskType;
      if (this.$options.components) {
        getAsyncModule(
          ModuleComponentType.PUBLIC_SCREEN,
          taskType,
          this.moduleName
        ).then((component) => {
          if (this.$options.components)
            this.$options.components['PublicScreenComponent'] = component;
          this.componentLoadIndex++;
        });
      }
      if (taskType) {
        this.$nextTick(() => {
          setModuleStyles(taskType);
        });
      }
    });
  }
}
</script>
<style lang="scss" scoped>
h3 {
  font-weight: var(--font-weight-bold);
}

.connection-key {
  font-size: 2rem;
  padding: 1rem 3rem;
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

  &__container {
    padding-top: 0;
    padding: 1rem 5rem;
  }

  &__overview {
    display: flex;
    justify-content: space-between;
    color: var(--color-primary);// white;

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
    color: white;
    margin: 0px;
    padding: 0px;
  }
}
</style>
