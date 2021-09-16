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
            <span class="icon" v-if="module.icon">
              <font-awesome-icon :icon="module.icon" />
            </span>
            <span>
              {{ $t(`enum.moduleType.${taskType}.${module.name}`) }}
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
  <ParticipantModuleComponent :task-id="taskId" :key="componentLoadIndex" />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

import { Task } from '@/types/api/Task';
import TaskType from '@/types/enum/TaskType';

import TaskStates from '@/types/enum/TaskStates';
import {
  getAsyncModule,
  getAsyncDefaultModule,
  getEmptyComponent,
  getModuleConfig
} from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import * as taskService from '@/services/task-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import MenuBar from '@/components/participant/molecules/Menubar.vue';

@Options({
  components: {
    MenuBar,
    ParticipantModuleComponent: getEmptyComponent(),
  },
})
export default class ParticipantModuleContent extends Vue {
  @Prop({ default: '' }) readonly sessionId!: string;
  @Prop({ default: '' }) readonly taskId!: string;

  // eslint-disable-next-line no-undef
  task: Task | null = null;

  TaskType = TaskType;
  TaskStates = TaskStates;
  moduleName = '';
  componentLoadIndex = 0;

  mounted(): void {
    getAsyncDefaultModule(ModuleComponentType.PARTICIPANT).then((component) => {
      if (this.$options.components)
        this.$options.components['ParticipantModuleComponent'] = component;
      this.componentLoadIndex++;
    });
  }

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
        this.task.modules.forEach((module) => this.setIcon(module));
        this.moduleNameClick(this.moduleNames[0]);
      });
  }

  async setIcon(module: any): Promise<void> {
    await getModuleConfig('icon', this.taskType, module.name).then(
      (result) => (module.icon = result)
    );
  }

  moduleNameClick(moduleName: string | null = null): void {
    if (!moduleName) {
      moduleName = this.moduleName;
    }
    if (moduleName) this.moduleName = moduleName;
    const taskType = this.taskType;
    if (this.$options.components) {
      getAsyncModule(
        ModuleComponentType.PARTICIPANT,
        taskType,
        moduleName
      ).then((component) => {
        if (this.$options.components)
          this.$options.components['ParticipantModuleComponent'] = component;
        this.componentLoadIndex++;
      });
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

.menubar {
  margin-bottom: 0.5rem;
}
</style>
