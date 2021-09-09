<template>
  <section>
    <label for="brainstorming_task" class="heading heading--xs">{{
      $t('module.categorisation.settings.taskParameter.brainstorming')
    }}</label>
    <select
      v-model="brainstormingTaskId"
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
      v-if="context.$v.brainstormingTaskId.$error"
      :errors="context.$v.brainstormingTaskId.$errors"
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
    brainstormingTaskId: {
      required,
    },
  },
})
export default class TaskParameter extends Vue implements CustomParameter {
  @Prop() readonly taskId!: string;
  @Prop({ default: {} }) modelValue!: any;
  task: Task | null = null;
  brainstormingTaskId: string | null = null;
  errors: string[] = [];
  brainstormingTasks: Task[] = [];

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  async loadBrainstormingTasks(): Promise<void> {
    if (this.task) {
      await taskService.getTaskList(this.task.topicId).then((tasks) => {
        this.brainstormingTasks = tasks.filter(
          (task) => task.taskType == TaskType.BRAINSTORMING.toUpperCase()
        );
      });
    }
  }

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    await this.getTask();
    if (this.task) {
      this.$emit('update:modelValue', this.task.parameter);
    }
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
        this.brainstormingTaskId = task.parameter.brainstormingTaskId;
      });
      await this.loadBrainstormingTasks();
    }
  }

  async save(): Promise<void> {
    if (this.task) {
      await taskService.putTask(this.taskId, {
        parameter: {
          brainstormingTaskId: this.brainstormingTaskId,
        },
      });
    }
  }
}
</script>
