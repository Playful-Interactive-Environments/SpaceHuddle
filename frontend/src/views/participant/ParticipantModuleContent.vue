<template>
  <div class="overlay">
    <MenuBar />
    <el-tabs
      :stretch="false"
      v-if="task && task.modules.length > 1"
      v-model="moduleName"
      class="white"
      @tab-click="(tab) => moduleNameClick(tab.paneName)"
    >
      <el-tab-pane
        v-for="module in task.modules"
        :key="module.name"
        :name="module.name"
      >
        <template #label>
          <span class="icon" v-if="module.icon">
            <font-awesome-icon :icon="module.icon" />
          </span>
          <span class="text">
            {{ $t(`module.${taskType}.${module.name}.description.title`) }}
          </span>
        </template>
      </el-tab-pane>
    </el-tabs>
  </div>
  <ParticipantModuleComponent
    :task-id="taskId"
    :module-id="moduleId"
    :key="componentLoadIndex"
  />
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
  getModuleConfig,
} from '@/modules';
import ModuleComponentType from '@/modules/ModuleComponentType';
import * as taskService from '@/services/task-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import MenuBar from '@/components/participant/molecules/Menubar.vue';
import { Module } from '@/types/api/Module';

@Options({
  components: {
    MenuBar,
    ParticipantModuleComponent: getEmptyComponent(),
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
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
    this.loadDefaultModule();
  }

  unmounted(): void {
    this.loadDefaultModule();
  }

  loadDefaultModule(): void {
    getAsyncDefaultModule(ModuleComponentType.PARTICIPANT).then((component) => {
      if (this.$options.components)
        this.$options.components['ParticipantModuleComponent'] = component;
      this.componentLoadIndex++;
    });
  }

  get module(): Module | null {
    if (this.task) {
      const module = this.task.modules.find(
        (module) => module.name == this.moduleName
      );
      if (module) return module;
    }
    return null;
  }

  get moduleId(): string | null {
    if (this.module) return this.module.id;
    return null;
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

  async setIcon(module: Module): Promise<void> {
    await getModuleConfig('icon', this.taskType, module.name).then(
      (result) => ((module as any).icon = result)
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
