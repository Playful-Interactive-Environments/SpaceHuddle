<template>
  <div class="public-screen">
    <Header :white="true" />
    <main
      class="public-screen__container container container--spaced"
      ref="container"
    >
      <section v-if="task" class="public-screen__overview">
        <div class="public-screen__overview-left">
          <span class="public-screen__overview-type">
            {{ $t(`enum.moduleType.${ModuleType[task.taskType]}`) }}
          </span>
          <h2>{{ task.name }}</h2>
          <p>
            {{ task.description }}
          </p>
        </div>
        <div class="public-screen__overview-right">
          <Timer :isActive="task.state === TaskStates.ACTIVE" />
          <img
            :src="
              require(`@/assets/illustrations/planets/${
                ModuleType[task.taskType]
              }.png`)
            "
            alt="planet"
            class="public-screen__overview-planet"
          />
        </div>
      </section>
      <PublicScreenComponent :task-id="taskId" />
    </main>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

import Header from '@/components/moderator/organisms/Header.vue';
import Timer from '@/components/shared/atoms/Timer.vue';

import * as sessionService from '@/services/session-service';
import { Task } from '@/types/api/Task';
import ModuleType from '@/types/enum/ModuleType';

import { setModuleStyles } from '@/utils/moduleStyles';
import TaskStates from '@/types/enum/TaskStates';
import { getModule, getDefaultModule } from '@/modules/loadComponent';
import ModuleComponentType from '@/modules/ModuleComponentType';

@Options({
  components: {
    Header,
    Timer,
    PublicScreenComponent: getDefaultModule(ModuleComponentType.PUBLIC_SCREEN),
  },
})
export default class PublicScreen extends Vue {
  @Prop() readonly sessionId!: string;

  task: Task | null = null;

  ModuleType = ModuleType;
  TaskStates = TaskStates;

  get taskId(): string | null {
    if (this.task) return this.task.id;
    return null;
  }

  get taskType(): ModuleType | null {
    if (this.task) return ModuleType[this.task.taskType];
    return null;
  }

  async mounted(): Promise<void> {
    sessionService.getPublicScreen(this.sessionId).then((queryResult) => {
      this.task = queryResult;
      const taskType = this.taskType;
      if (this.$options.components) {
        this.$options.components['PublicScreenComponent'] = getModule(
          ModuleComponentType.PUBLIC_SCREEN,
          taskType
        );
      }
      if (taskType) {
        this.$nextTick(() => {
          setModuleStyles(this.$refs.container as HTMLElement, taskType);
        });
      }
    });
  }
}
</script>
<style lang="scss" scoped>
.public-screen {
  background: url('../assets/illustrations/stars-background_without_dust.png');
  background-size: contain;
  min-height: 100vh;

  &__container {
    padding-top: 0;
  }

  &__overview {
    display: flex;
    justify-content: space-between;
    color: white;

    &-planet {
      height: 9rem;
      margin-left: 2rem;
      margin-top: -20px;
      margin-right: -20px;
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
    column-width: 20vw;
    column-gap: 1rem;
  }

  &__error {
    color: white;
    margin: 0px;
    padding: 0px;
  }
}
</style>
