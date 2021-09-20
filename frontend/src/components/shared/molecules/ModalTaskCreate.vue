<template>
  <div>
    <el-dialog
      :title="$t('moderator.organism.module.create.header')"
      v-model="showDialog"
      :before-close="handleClose"
      center
      :key="componentLoadIndex"
    >
      <div class="module-create">
        <p>
          {{ $t('moderator.organism.module.create.info') }}
        </p>
        <form class="module-create__form">
          <label for="taskType" class="heading heading--xs">{{
              $t('moderator.organism.module.create.taskType')
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
              $t('moderator.organism.module.create.moduleType')
            }}</label>
          <el-select v-model="moduleType" id="moduleType" multiple>
            <el-option
              v-for="type in moduleTypeKeys"
              :key="type"
              :value="type"
              :label="$t(`module.${TaskType[taskType]}.${type}.description.title`)"
            />
          </el-select>
          <FormError
            v-if="context.$v.taskType.$error"
            :errors="context.$v.taskType.$errors"
            :isSmall="true"
          />
          <Expand
            v-for="component in moduleParameterComponents.filter(
            (component) => component.hasModule
          )"
            :key="component.componentName"
          >
            <template v-slot:title>
              <font-awesome-icon
                :icon="component.moduleIcon"
                v-if="component.moduleIcon"
              />
              {{
                $t(`module.${TaskType[taskType]}.${component.moduleName}.description.title`)
              }}
            </template>
            <template v-slot:content>
              <component
                :ref="component.componentName"
                v-model="component.parameter"
                :module-id="component.moduleId"
                :is="component.componentName"
                :key="component.componentName"
              ></component>
            </template>
          </Expand>
          <label for="title" class="heading heading--xs">{{
              taskType === 'BRAINSTORMING'
                ? $t('moderator.organism.module.create.question')
                : $t('moderator.organism.module.create.title')
            }}</label>
          <input
            id="title"
            v-model="title"
            class="input input--fullwidth"
            :placeholder="$t('moderator.organism.module.create.questionExample')"
            @blur="context.$v.title.$touch()"
          />
          <FormError
            v-if="context.$v.title.$error"
            :errors="context.$v.title.$errors"
            :isSmall="true"
          />

          <label for="description" class="heading heading--xs">{{
              $t('moderator.organism.module.create.description')
            }}</label>
          <textarea
            id="description"
            v-model="description"
            class="textarea textarea--fullwidth"
            rows="3"
            :placeholder="
            $t('moderator.organism.module.create.descriptionExample')
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
          {{ $t('moderator.organism.module.create.submit') }}
        </button>
      </template>
    </el-dialog>
  </div>

  <!--
  <ModalBase
    v-model:show-modal="showModal"
    @update:showModal="updateVisibility($event)"
    :key="componentLoadIndex"
    v-on:opened="modalOpened()"
  >
    <div class="module-create">
      <h2 class="heading heading--regular">
        {{ $t('moderator.organism.module.create.header') }}
      </h2>
      <p>
        {{ $t('moderator.organism.module.create.info') }}
      </p>
      <form class="module-create__form">
        <label for="taskType" class="heading heading--xs">{{
          $t('moderator.organism.module.create.taskType')
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
          $t('moderator.organism.module.create.moduleType')
        }}</label>
        <el-select v-model="moduleType" id="moduleType" multiple>
          <el-option
            v-for="type in moduleTypeKeys"
            :key="type"
            :value="type"
            :label="$t(`module.${TaskType[taskType]}.${type}.description.title`)"
          />
        </el-select>
        <FormError
          v-if="context.$v.taskType.$error"
          :errors="context.$v.taskType.$errors"
          :isSmall="true"
        />
        <Expand
          v-for="component in moduleParameterComponents.filter(
            (component) => component.hasModule
          )"
          :key="component.componentName"
        >
          <template v-slot:title>
            <font-awesome-icon
              :icon="component.moduleIcon"
              v-if="component.moduleIcon"
            />
            {{
              $t(`module.${TaskType[taskType]}.${component.moduleName}.description.title`)
            }}
          </template>
          <template v-slot:content>
            <component
              :ref="component.componentName"
              v-model="component.parameter"
              :module-id="component.moduleId"
              :is="component.componentName"
              :key="component.componentName"
            ></component>
          </template>
        </Expand>
        <label for="title" class="heading heading--xs">{{
          taskType === 'BRAINSTORMING'
            ? $t('moderator.organism.module.create.question')
            : $t('moderator.organism.module.create.title')
        }}</label>
        <input
          id="title"
          v-model="title"
          class="input input--fullwidth"
          :placeholder="$t('moderator.organism.module.create.questionExample')"
          @blur="context.$v.title.$touch()"
        />
        <FormError
          v-if="context.$v.title.$error"
          :errors="context.$v.title.$errors"
          :isSmall="true"
        />

        <label for="description" class="heading heading--xs">{{
          $t('moderator.organism.module.create.description')
        }}</label>
        <textarea
          id="description"
          v-model="description"
          class="textarea textarea--fullwidth"
          rows="3"
          :placeholder="
            $t('moderator.organism.module.create.descriptionExample')
          "
          @blur="context.$v.description.$touch"
        />
        <FormError
          v-if="context.$v.description.$error"
          :errors="context.$v.description.$errors"
          :isSmall="true"
        />
        <button
          type="submit"
          class="btn btn--gradient btn--fullwidth"
          @click.prevent="saveModule"
        >
          {{ $t('moderator.organism.module.create.submit') }}
        </button>
      </form>
    </div>
  </ModalBase>-->
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import useVuelidate from '@vuelidate/core';
import { maxLength, required } from '@vuelidate/validators';

import FormError from '@/components/shared/atoms/FormError.vue';
import ModalBase from '@/components/shared/molecules/ModalBase.vue';

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
import Expand from '@/components/shared/atoms/Expand.vue';
import { Module } from '@/types/api/Module';

@Options({
  components: {
    Expand,
    FormError,
    ModalBase,
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
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModalTaskCreate extends Vue {
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

  moduleTypeKeys: string[] = [];
  taskType = this.TaskTypeKeys[1];
  moduleType = this.moduleTypeKeys;
  title = '';
  description = '';
  taskParameterValues: any = {};
  errors: string[] = [];
  task: Task | null = null;
  firstVisibility = true;
  showDialog = false;

  TaskType = TaskType;

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  modalOpened(): void {
    if (this.firstVisibility) {
      this.componentLoadIndex++;
      this.firstVisibility = false;
    }
    //todo: reset form do not work -> Uncaught (in promise) TypeError: Cannot read properties of null (reading 'insertBefore')
    /*else if (this.topicId) {
      this.resetForm();
      this.context.$v.$reset();
    }*/
  }

  handleClose(done) {
    this.resetForm();
    this.context.$v.$reset();
    done();
    this.$emit('update:showModal', false);
  }

  @Watch('showModal', { immediate: false, flush: 'post' })
  async onShowModalChanged(showModal: boolean): Promise<void> {
    this.showDialog = showModal;
  }

  @Watch('taskId', { immediate: true })
  onTaskIdChanged(id: string): void {
    if (id) {
      taskService.getTaskById(id).then((task) => {
        this.taskType = task.taskType;
        this.title = task.name;
        this.description = task.description;
        this.taskParameterValues = task.parameter ?? {};
        this.moduleType = task.modules.map((module) => module.name);
        this.task = task;
      });
    }
  }

  @Watch('taskType', { immediate: true })
  async onTaskTypeChanged(taskType: string): Promise<void> {
    this.loadModuleTypeKeys();
    if (this.$options.components) {
      getAsyncTaskParameter(TaskType[taskType]).then((component) => {
        if (this.$options.components)
          this.$options.components['TaskParameterComponent'] = component;
        this.componentLoadIndex++;
      });
    }
  }

  @Watch('moduleType', { immediate: true })
  async onModuleType(moduleType: string[]): Promise<void> {
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
      });
    };

    if (this.$options.components) {
      this.moduleParameterComponents = []; //moduleType.map((moduleName) => `ModuleParameterComponents:${moduleName}`);
      moduleType.forEach((moduleName) => {
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

  async loadModuleTypeKeys(): Promise<void> {
    this.moduleTypeKeys = [];
    this.moduleType = this.moduleTypeKeys;
    await getModulesForTaskType(this.taskType).then((result) => {
      this.moduleTypeKeys = result;
      this.moduleType = this.moduleTypeKeys;
    });
  }

  resetForm(): void {
    this.taskType = this.TaskTypeKeys[1];
    this.moduleType = this.moduleTypeKeys;
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
          modules: this.moduleType,
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
          modules: this.moduleType,
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

  async updateVisibility(showModal: boolean): Promise<void> {
    this.$emit('update:showModal', showModal);
    if (!showModal) {
      //todo: reset errors and form do not work -> Uncaught (in promise) TypeError: Cannot read properties of null (reading 'insertBefore')
      //if (this.topicId) this.resetForm();
      //this.context.$v.$reset();
    }
  }
}
</script>

<style lang="scss" scoped>
.module-create {
  display: flex;
  flex-direction: column;
  padding: 0 1rem;
  line-height: 1.2;

  &__form {
    display: flex;
    flex-direction: column;

    input,
    textarea {
      margin: 0 0 0.2rem;
    }
  }

  &__topic {
    margin-top: 1rem;
  }
}
</style>
