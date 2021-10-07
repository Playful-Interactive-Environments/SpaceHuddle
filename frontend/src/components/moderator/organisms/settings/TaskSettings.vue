<template>
  <div>
    <el-dialog
      v-model="showDialog"
      :before-close="handleClose"
      :key="componentLoadIndex"
      width="80vw"
    >
      <template #title>
        <span class="el-dialog__title">{{
          $t('moderator.organism.settings.taskSettings.header')
        }}</span>
        <br />
        <br />
        <p>
          {{ $t('moderator.organism.settings.taskSettings.info') }}
        </p>
      </template>

      <div>
        <form>
          <label for="taskType" class="heading heading--xs">{{
            $t('moderator.organism.settings.taskSettings.taskType')
          }}</label>
          <select
            v-model="taskType"
            id="taskType"
            class="select select--fullwidth"
          >
            <option v-for="type in TaskTypeKeys" :key="type" :value="type">
              {{ $t(`enum.taskType.${TaskType[type]}`) }}
            </option>
          </select>
          <FormError
            v-if="context.$v.taskType.$error"
            :errors="context.$v.taskType.$errors"
            :isSmall="true"
          />
          <TaskParameterComponent
            ref="taskParameter"
            :taskId="taskId"
            :topicId="topicId"
            v-model="taskParameterValues"
          />
          <label for="moduleType" class="heading heading--xs">{{
            $t('moderator.organism.settings.taskSettings.moduleType')
          }}</label>
          <div>
            <el-tag
              v-for="tag in moduleSelection"
              :key="tag"
              :closable="moduleKeyList.length > 1"
              v-on:close="removeModuleType(tag)"
            >
              {{ $t(`module.${TaskType[taskType]}.${tag}.description.title`) }}
            </el-tag>
          </div>
          <el-carousel
            id="moduleType"
            :autoplay="false"
            type="card"
            arrow="always"
            height="250px"
            v-if="moduleKeyList.length > 1"
          >
            <el-carousel-item
              v-for="moduleType in moduleKeyList"
              :key="moduleType"
            >
              <ModuleCard
                :task-type="TaskType[taskType]"
                :moduleName="moduleType"
                v-model="moduleList[moduleType]"
              />
            </el-carousel-item>
          </el-carousel>
          <FormError
            v-if="context.$v.moduleSelection.$error"
            :errors="context.$v.moduleSelection.$errors"
            :isSmall="true"
          />
          <el-collapse v-model="openTabs" :key="openTabs.length" v-if="moduleParameterComponents.filter(
                (component) => component.hasModule
              ).length > 0">
            <el-collapse-item
              v-for="component in moduleParameterComponents.filter(
                (component) => component.hasModule
              )"
              :key="component.componentName"
              :name="component.componentName"
            >
              <template #title>
                <span>
                  <font-awesome-icon
                    :icon="component.moduleIcon"
                    v-if="component.moduleIcon"
                  />
                  {{
                    $t(
                      `module.${TaskType[taskType]}.${component.moduleName}.description.title`
                    )
                  }}
                </span>
              </template>
              <component
                :ref="component.componentName"
                v-model="component.parameter"
                :module-id="component.moduleId"
                :is="component.componentName"
                :key="component.componentName"
              ></component>
            </el-collapse-item>
          </el-collapse>
          <label for="taskTitle" class="heading heading--xs">{{
            taskType === 'BRAINSTORMING'
              ? $t('moderator.organism.settings.taskSettings.question')
              : $t('moderator.organism.settings.taskSettings.title')
          }}</label>
          <input
            id="taskTitle"
            v-model="title"
            class="input input--fullwidth"
            :placeholder="
              $t('moderator.organism.settings.taskSettings.questionExample')
            "
            @blur="context.$v.title.$touch()"
          />
          <FormError
            v-if="context.$v.title.$error"
            :errors="context.$v.title.$errors"
            :isSmall="true"
          />

          <label for="description" class="heading heading--xs">{{
            $t('moderator.organism.settings.taskSettings.description')
          }}</label>
          <textarea
            id="description"
            v-model="description"
            class="textarea textarea--fullwidth"
            rows="3"
            :placeholder="
              $t('moderator.organism.settings.taskSettings.descriptionExample')
            "
            @blur="context.$v.description.$touch"
          />
          <FormError
            v-if="context.$v.description.$error"
            :errors="context.$v.description.$errors"
            :isSmall="true"
          />
        </form>
      </div>
      <template #footer>
        <button
          type="submit"
          class="btn btn--gradient btn--fullwidth"
          @click.prevent="saveModule"
        >
          {{ $t('moderator.organism.settings.taskSettings.submit') }}
        </button>
      </template>
    </el-dialog>
  </div>
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import useVuelidate from '@vuelidate/core';
import { maxLength, required } from '@vuelidate/validators';

import FormError from '@/components/shared/atoms/FormError.vue';

import * as taskService from '@/services/task-service';
import * as moduleService from '@/services/module-service';
import TaskType from '@/types/enum/TaskType';
import { Task } from '@/types/api/Task';
import {
  getErrorMessage,
  addError,
  clearErrors,
} from '@/services/exception-service';
import {
  getAsyncTaskParameter,
  getAsyncModule,
  getEmptyComponent,
  getModulesForTaskType,
  hasModule,
  getModuleConfig,
} from '@/modules';
import { CustomParameter } from '@/types/ui/CustomParameter';
import { EventType } from '@/types/enum/EventType';
import ModuleComponentType from '@/modules/ModuleComponentType';
import { Module } from '@/types/api/Module';
import ModuleCard from '@/components/moderator/organisms/cards/ModuleCard.vue';
import ModuleItem from '@/components/shared/molecules/ModuleItem.vue';

@Options({
  components: {
    FormError,
    ModuleCard,
    ModuleItem,
    TaskParameterComponent: getEmptyComponent(),
  },
  validations: {
    taskType: {
      required,
    },
    title: {
      required,
      max: maxLength(255),
    },
    description: {
      required,
      max: maxLength(1000),
    },
    moduleSelection: {
      required,
    },
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskSettings extends Vue {
  @Prop({ default: false }) showModal!: boolean;
  @Prop({}) topicId!: string;
  @Prop({}) taskId!: string;
  componentLoadIndex = 0;

  moduleParameterComponents: {
    componentName: string;
    moduleName: string | null;
    moduleIcon: string | null;
    moduleId: string | null;
    hasModule: boolean;
    parameter: any;
  }[] = [];
  openTabs: string[] = [];

  moduleList: { [key: string]: boolean } = {};
  taskType = this.TaskTypeKeys[1];
  title = '';
  description = '';
  taskParameterValues: any = {};
  errors: string[] = [];
  task: Task | null = null;

  TaskType = TaskType;

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  showDialog = false;
  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  get moduleKeyList(): string[] {
    return Object.keys(this.moduleList);
  }

  get moduleSelection(): string[] {
    return Object.entries(this.moduleList)
      .filter((item) => item[1])
      .map((item) => item[0]);
  }

  removeModuleType(tag: string): void {
    this.moduleList[tag] = false;
  }

  handleClose(done: { (): void }): void {
    if (this.topicId) this.resetForm();
    this.context.$v.$reset();
    done();
    this.$emit('update:showModal', false);
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(id: string): void {
    if (id) {
      taskService.getTaskById(id).then((task) => {
        this.taskType = task.taskType;
        this.title = task.name;
        this.description = task.description;
        this.taskParameterValues = task.parameter ?? {};
        task.modules.forEach((module) => {
          this.moduleList[module.name] = true;
        });
        this.task = task;
      });
    }
  }

  @Watch('taskType', { immediate: true })
  async onTaskTypeChanged(taskType: string): Promise<void> {
    await this.loadModuleList();
    if (this.$options.components) {
      getAsyncTaskParameter(TaskType[taskType]).then((component) => {
        if (this.$options.components)
          this.$options.components['TaskParameterComponent'] = component;
        this.componentLoadIndex++;
      });
    }
  }

  @Watch('moduleSelection', { immediate: true })
  async onModuleSelectionChanged(moduleSelection: string[]): Promise<void> {
    const oldKeys = this.moduleParameterComponents.map(
      (data) => data.componentName
    );

    const addComponent = async (
      moduleName: string,
      componentName: string
    ): Promise<void> => {
      let moduleId: string | null = null;
      let moduleParameter: any = {};
      if (this.task) {
        const componentModule = this.task.modules.find(
          (module) => module.name == moduleName
        );
        if (componentModule) {
          moduleId = componentModule.id;
          moduleParameter = componentModule.parameter;
        }
      }

      let icon: string | null = null;
      await getModuleConfig('icon', TaskType[this.taskType], moduleName).then(
        (result) => (icon = result)
      );

      hasModule(
        ModuleComponentType.MODERATOR_CONFIG,
        TaskType[this.taskType],
        moduleName
      ).then((result) => {
        this.moduleParameterComponents.push({
          componentName: componentName,
          moduleId: moduleId,
          moduleName: moduleName,
          moduleIcon: icon,
          hasModule: result,
          parameter: moduleParameter,
        });
        if (
          !this.openTabs.includes(componentName) &&
          !oldKeys.includes(componentName)
        )
          this.openTabs.push(componentName);
      });
    };

    if (this.$options.components) {
      this.moduleParameterComponents = [];
      moduleSelection.forEach((moduleName) => {
        const componentName = `ModuleParameterComponents-${this.taskType}-${moduleName}`;
        if (
          this.$options.components &&
          !this.$options.components[componentName]
        ) {
          getAsyncModule(
            ModuleComponentType.MODERATOR_CONFIG,
            TaskType[this.taskType],
            moduleName
          ).then((component) => {
            if (this.$options.components)
              this.$options.components[componentName] = component;
            addComponent(moduleName, componentName);
          });
        } else {
          addComponent(moduleName, componentName);
        }
      });
    }
    //this.componentLoadIndex++;
  }

  get TaskTypeKeys(): Array<keyof typeof TaskType> {
    return Object.keys(TaskType) as Array<keyof typeof TaskType>;
  }

  async loadModuleList(): Promise<void> {
    this.moduleList = {};
    await getModulesForTaskType(this.taskType).then((result) => {
      result.forEach((moduleName, moduleIndex) => {
        if (!this.task) this.moduleList[moduleName] = moduleIndex == 0;
        else
          this.moduleList[moduleName] = this.task.modules.some(
            (model) => model.name == moduleName
          );
      });
    });
  }

  resetForm(): void {
    this.taskType = this.TaskTypeKeys[1];
    this.loadModuleList();
    this.title = '';
    this.description = '';
    this.taskParameterValues = {};
    this.task = null;
  }

  async saveModule(): Promise<void> {
    clearErrors(this.errors);
    await this.context.$v.$reset();
    await this.context.$v.$validate();
    if (this.context.$v.$error) return;

    await this.updateCustomTaskParameter();

    if (this.topicId) {
      let taskCount = 0;
      await taskService.getTaskList(this.topicId).then((tasks) => {
        taskCount = tasks.length;
      });

      taskService
        .postTask(this.topicId, {
          taskType: this.taskType,
          name: this.title,
          description: this.description,
          parameter: this.taskParameterValues,
          order: taskCount,
          modules: this.moduleSelection,
        })
        .then(
          (task) => {
            this.taskUpdated(task, true);
          },
          (error) => {
            addError(this.errors, getErrorMessage(error));
          }
        );
    } else if (this.taskId) {
      taskService
        .putTask(this.taskId, {
          taskType: this.taskType,
          name: this.title,
          description: this.description,
          parameter: this.taskParameterValues,
          modules: this.moduleSelection,
          state: this.task?.state,
        })
        .then(
          (task) => {
            this.taskUpdated(task, false);
          },
          (error) => {
            addError(this.errors, getErrorMessage(error));
          }
        );
    }
  }

  async updateCustomTaskParameter(): Promise<void> {
    if (this.$refs.taskParameter) {
      const params = this.$refs.taskParameter as CustomParameter;
      if ('updateParameterForSaving' in params)
        await params.updateParameterForSaving();
    }
  }

  async updateCustomModuleParameter(task: Task, module: Module): Promise<void> {
    const componentName = `ModuleParameterComponents-${task.taskType}-${module.name}`;
    if (this.$refs[componentName]) {
      const moduleParams = this.$refs[componentName] as CustomParameter;
      if ('updateParameterForSaving' in moduleParams)
        await moduleParams.updateParameterForSaving();
    }
  }

  async taskUpdated(task: Task, cleanUp = true): Promise<void> {
    task.modules.forEach((module) => {
      this.updateCustomModuleParameter(task, module).then(() => {
        const moduleComponent = this.moduleParameterComponents.find(
          (component) => component.moduleName == module.name
        );
        if (moduleComponent) {
          module.parameter = moduleComponent.parameter;
          moduleService.putModule(module.id, module);
        }
      });
    });
    this.$emit('update:showModal', false);
    this.$emit('moduleCreated');
    if (cleanUp) this.resetForm();
    this.context.$v.$reset();
    this.eventBus.emit(EventType.CHANGE_SETTINGS, {});
  }
}
</script>

<style lang="scss" scoped>
</style>
