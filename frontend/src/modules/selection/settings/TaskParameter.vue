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
    selectionName: {
      required,
      max: maxLength(255),
    },
    brainstormingTaskId : {
      required,
    }
  },
})
export default class TaskParameter extends Vue implements CustomParameter {
  @Prop() readonly taskId!: string;
  @Prop({ default: {} }) modelValue!: any;
  task: Task | null = null;
  selectionName = '';
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
      //selection
      this.$emit('update:modelValue', this.task.parameter);
      if (
        this.task.parameter &&
        this.task.parameter.selectionId &&
        this.selectionName.length == 0
      ) {
        await selectionService
          .getSelectionById(this.task.parameter.selectionId)
          .then((selection) => {
            this.selectionName = selection.name;
          });
        //await selectionService.postSelection(this.task.topicId, {name: this.selectionName});
      }
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
      if (!this.task.parameter || !this.task.parameter.selectionId) {
        await selectionService
          .postSelection(this.task.topicId, { name: this.selectionName })
          .then((selection) => {
            taskService.putTask(this.taskId, {
              parameter: {
                selectionId: selection.id,
                brainstormingTaskId: this.brainstormingTaskId,
              },
            });
          });
      } else {
        await selectionService.putSelection(this.task.parameter.selectionId, {
          name: this.selectionName,
        });
        await taskService.putTask(this.taskId, {
          parameter: {
            selectionId: this.task.parameter.selectionId,
            brainstormingTaskId: this.brainstormingTaskId,
          },
        });
      }
    }
  }
}
</script>
