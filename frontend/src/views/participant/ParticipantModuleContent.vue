<template>
  <div class="overlay">
    <MenuBar />
    <div
      class="tabs is-fullwidth is-centered"
      v-if="task && task.modules.length > 1"
    >
      <ul>
        <li
          v-for="module in task.modules"
          :key="module.name"
          :class="moduleName === module.name ? 'is-active' : ''"
        >
          <a v-on:click="moduleNameClick(module.name)">
            <span class="icon">
              <i class="fas fa-image" aria-hidden="true"></i>
            </span>
            <span>
              {{$t(`enum.moduleType.${taskType}.${module.name}`)}}
            </span>
          </a>
        </li>
      </ul>
    </div>

    <!--<el-tabs v-model="moduleName" @tab-click="moduleNameClick" v-if="task && task.modules.length > 1">
      <el-tab-pane
        v-for="module in task.modules"
        :key="module.name"
        :name="module.name"
        :label="$t(`enum.moduleType.${taskType}.${module.name}`)"
      />
    </el-tabs>-->
  </div>
  <ParticipantModuleComponent :task-id="taskId" />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

import { Task } from '@/types/api/Task';
import TaskType from '@/types/enum/TaskType';

import TaskStates from '@/types/enum/TaskStates';
import { getAsyncModule, getAsyncDefaultModule } from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import * as taskService from '@/services/task-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import MenuBar from '@/components/participant/molecules/Menubar.vue';

@Options({
  components: {
    MenuBar,
    ParticipantModuleComponent: getAsyncDefaultModule(
      ModuleComponentType.PARTICIPANT
    ),
  },
})
export default class ParticipantModuleContent extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;

  // eslint-disable-next-line no-undef
  task: Task | null = null;

  TaskType = TaskType;
  TaskStates = TaskStates;
  moduleName = this.moduleNames[0];

  // eslint-disable-next-line no-undef
  get taskType(): TaskType | null {
    if (this.task) return TaskType[this.task.taskType];
    return null;
  }

  get moduleNames(): string[] {
    if (this.task && this.task.modules && this.task.modules.length > 0) {
      let modules: string[] = [];
      this.task.modules.forEach((module) => {
        modules.push(module.name);
      });
      console.log(modules);
      return modules;
    }
    return ['default'];
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(val: string): void {
    taskService
      .getTaskById(val, EndpointAuthorisationType.PARTICIPANT)
      .then((queryResult) => {
        this.task = queryResult;
        this.moduleNameClick();
      });
  }

  moduleNameClick(moduleName: string|null = null): void {
    if (!moduleName) {
      moduleName = this.moduleName;
    }
    this.moduleName = moduleName;
    const taskType = this.taskType;
    if (this.$options.components) {
      this.$options.components['ParticipantModuleComponent'] = getAsyncModule(
        ModuleComponentType.PARTICIPANT,
        taskType,
        moduleName
      );
    }
  }
}
</script>
<style lang="scss" scoped>
.overlay {
  position: fixed;
  top: 0px;
  z-index: 20;
  width: 100%;
  padding: 10px 20px 0px 20px;
}

.menubar{
  margin-bottom: 0.5rem;
}
</style>
