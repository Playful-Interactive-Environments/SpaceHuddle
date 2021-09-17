<template>
  <section>
    <label for="name" class="heading heading--xs">{{
      $t('module.selection.settings.taskParameter.name')
    }}</label>
    <input
      id="name"
      v-model="selectionName"
      class="input input--fullwidth"
      :placeholder="$t('module.selection.settings.taskParameter.nameExample')"
      @blur="context.$v.selectionName.$touch()"
    />
    <FormError
      v-if="context.$v.selectionName.$error"
      :errors="context.$v.selectionName.$errors"
      :isSmall="true"
    />
    <label for="brainstorming_task" class="heading heading--xs">{{
      $t('module.selection.settings.taskParameter.brainstorming')
    }}</label>
    <select
      v-model="modelValue.brainstormingTaskId"
      id="brainstorming_task"
      class="select select--fullwidth"
    >
      <option
        v-for="task in brainstormingTasks"
        :key="task.id"
        :value="task.id"
      >
        {{ task.name }}
      </option>
    </select>
    <FormError
      v-if="context.$v.modelValue.brainstormingTaskId.$error"
      :errors="context.$v.modelValue.brainstormingTaskId.$errors"
      :isSmall="true"
    />
  </section>
</template>

<script lang="ts">
import { Options, setup, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import * as selectionService from '@/services/selection-service';
import { Task } from '@/types/api/Task';
import { maxLength, required } from '@vuelidate/validators';
import useVuelidate from '@vuelidate/core';
import FormError from '@/components/shared/atoms/FormError.vue';
import { CustomParameter } from '@/types/ui/CustomParameter';
import TaskType from '@/types/enum/TaskType';

@Options({
  components: {
    FormError,
  },
  validations: {
    selectionName: {
      required,
      max: maxLength(255),
    },
    modelValue: {
      brainstormingTaskId: {
        required,
      },
    },
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskParameter extends Vue implements CustomParameter {
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  task: Task | null = null;
  errors: string[] = [];
  brainstormingTasks: Task[] = [];
  selectionName = '';

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      if (this.modelValue.selectionId) {
        await selectionService
          .getSelectionById(this.modelValue.selectionId)
          .then((selection) => {
            this.selectionName = selection.name;
          });
      }
      if (!this.modelValue.brainstormingTaskId)
        this.modelValue.brainstormingTaskId = null;
    }
  }

  async loadBrainstormingTasks(): Promise<void> {
    const topicId = this.task ? this.task.topicId : this.topicId;

    if (topicId) {
      await taskService.getTaskList(topicId).then((tasks) => {
        this.brainstormingTasks = tasks.filter(
          (task) => task.taskType == TaskType.BRAINSTORMING.toUpperCase()
        );
      });
    }
  }

  @Watch('topicId', { immediate: true })
  async onTopicIdChanged(): Promise<void> {
    await this.loadBrainstormingTasks();
  }

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    await this.getTask();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
      await this.loadBrainstormingTasks();
    }
  }

  async updateParameterForSaving(): Promise<void> {
    if (this.modelValue) {
      if (this.modelValue.selectionId) {
        const selectionId = this.modelValue.selectionId;
        await selectionService.putSelection(selectionId, {
          name: this.selectionName,
        });
      } else {
        const topicId = this.task ? this.task.topicId : this.topicId;
        if (topicId) {
          await selectionService
            .postSelection(topicId, {name: this.selectionName})
            .then((selection) => {
              this.modelValue.selectionId = selection.id;
            });
        }
      }
    }
  }
}
</script>
