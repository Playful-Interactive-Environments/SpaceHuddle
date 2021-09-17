<template>
  <section>
    <label for="brainstorming_task" class="heading heading--xs">{{
      $t('module.categorisation.settings.taskParameter.brainstorming')
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
import { Task } from '@/types/api/Task';
import { required } from '@vuelidate/validators';
import useVuelidate from '@vuelidate/core';
import FormError from '@/components/shared/atoms/FormError.vue';
import TaskType from '@/types/enum/TaskType';

@Options({
  components: {
    FormError,
  },
  validations: {
    modelValue: {
      brainstormingTaskId: {
        required,
      },
    },
  },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskParameter extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  task: Task | null = null;
  errors: string[] = [];
  brainstormingTasks: Task[] = [];

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.brainstormingTaskId) {
      this.modelValue.brainstormingTaskId = null;
    }
  }

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

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

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    await this.getTask();
  }

  @Watch('topicId', { immediate: true })
  async onTopicIdChanged(): Promise<void> {
    await this.loadBrainstormingTasks();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
      await this.loadBrainstormingTasks();
    }
  }
}
</script>
