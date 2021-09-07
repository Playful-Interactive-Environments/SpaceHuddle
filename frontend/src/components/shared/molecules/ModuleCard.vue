<template>
  <router-link
    :to="
      isParticipant
        ? `/participant-module-content/${task.id}`
        : `/module-content/${sessionId}/${task.id}`
    "
  >
    <article
      ref="item"
      class="module-card"
      :class="{ 'module-card--participant': isParticipant }"
    >
      <img
        :src="require(`@/assets/illustrations/planets/${type}.png`)"
        alt="planet"
        class="module-card__planet"
      />
      <ModuleInfo
        :type="type"
        :title="task.name"
        :description="task.description"
      />
      <Timer
        class="module-card__timer"
        :isActive="task.state === TaskStates.ACTIVE"
        v-if="!(type === TaskType.INFORMATION || type === TaskType.SELECTION)"
      />
      <div class="module-card__toggles" v-if="!isParticipant">
        <ModuleShare :task="task" :is-on-public-screen="isOnPublicScreen" />
      </div>
      <div class="module-card__drag" v-if="!isParticipant">
        <font-awesome-icon
          icon="grip-vertical"
          class="module-card__drag__icon"
        />
      </div>
    </article>
  </router-link>
</template>

<script lang="ts">
import { Prop } from 'vue-property-decorator';
import { Options, Vue } from 'vue-class-component';
import { setModuleStyles } from '@/utils/moduleStyles';
import { Task } from '@/types/api/Task';
import ModuleInfo from '@/components/shared/molecules/ModuleInfo.vue';
import Timer from '@/components/shared/atoms/Timer.vue';
import ModuleShare from '@/components/moderator/molecules/ModuleShare.vue';
import TaskType from '@/types/enum/TaskType';
import TaskStates from '@/types/enum/TaskStates';

@Options({
  components: {
    ModuleInfo,
    Timer,
    ModuleShare,
  },
})
export default class ModuleCard extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: TaskType.BRAINSTORMING }) type!: TaskType;
  @Prop() task!: Task;
  @Prop({ default: false }) isParticipant!: boolean;
  @Prop({ default: false }) isOnPublicScreen!: boolean;

  TaskType = TaskType;
  TaskStates = TaskStates;

  get moduleName(): string {
    if (this.task && this.task.modules && this.task.modules.length > 0)
      return this.task.modules[0].name;
    return 'default';
  }

  mounted(): void {
    setModuleStyles(this.$refs.item as HTMLElement, this.type);
  }

  updated(): void {
    setModuleStyles(this.$refs.item as HTMLElement, this.type);
  }
}
</script>

<style lang="scss" scoped>
.module-card {
  position: relative;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background-color: white;
  border-radius: var(--border-radius);
  box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
  padding: 1.5rem 5rem 1.5rem 4.5rem;
  margin-left: 3rem;

  &:hover {
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
    transition: all 200ms;
  }

  &__planet {
    position: absolute;
    left: -4rem;
    top: 50%;
    transform: translateY(-50%);
    width: 8rem;
  }

  &__toggles {
    display: flex;
    flex-direction: column;
    margin-left: 2rem;
    width: 12rem;
  }

  &__drag {
    position: absolute;
    top: 0;
    right: 0;
    bottom: 0;
    background-color: var(--color-mint);
    background-color: var(--module-color);
    border-radius: 0 var(--border-radius) var(--border-radius) 0;
    display: flex;
    justify-content: center;
    align-items: center;
    width: 35px;
    align-self: stretch;
    cursor: pointer;

    &__icon {
      color: white;
    }
  }

  &__dots-icon {
    width: 12px;
    height: auto;
  }

  &--participant {
    flex-direction: column;
    margin-left: 0;
    padding: 1.5rem 1.5rem 2.2rem;
    text-align: center;
    color: var(--color-darkblue);
    width: 65vw;
    margin-bottom: 1.5rem;
    min-height: calc(100% - 18.4px);

    .module-card__planet {
      position: static;
      transform: none;
      margin-bottom: 0.5rem;
    }

    .module-card__timer {
      position: absolute;
      bottom: 0;
      transform: translateY(50%);
    }
  }
}
</style>
