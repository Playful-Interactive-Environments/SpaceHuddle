<template>
  <section>
    <label for="selection" class="heading heading--xs">{{
        $t('module.voting.settings.taskParameter.selection')
      }}</label>
    <select
      v-model="selectionId"
      id="selection"
      class="select select--fullwidth"
    >
      <option
        v-for="selection in selections"
        :key="selection.id"
        :value="selection.id"
      >
        {{ selection.name }}
      </option>
    </select>
    <FormError
      v-if="context.$v.selectionId.$error"
      :errors="context.$v.selectionId.$errors"
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
import { Selection } from '@/types/api/Selection';
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
    selectionId: {
      required,
    },
  },
})
export default class TaskParameter extends Vue implements CustomParameter {
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  task: Task | null = null;
  selectionId: string | null = null;
  errors: string[] = [];
  selections: Selection[] = [];

  context = setup(() => {
    return {
      $v: useVuelidate(),
    };
  });

  async loadSelections(): Promise<void> {
    if (this.task) {
      await selectionService
        .getSelectionForTopic(this.task.topicId)
        .then((selection) => {
          this.selections = selection;
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
        this.selectionId = task.parameter.selectionId;
      });
      await this.loadSelections();
    }
  }

  async save(taskId: string | null): Promise<void> {
    if (!taskId) taskId = this.taskId;
    if (taskId) {
      await taskService.putTask(taskId, {
        parameter: {
          selectionId: this.selectionId,
        },
      });
    }
  }
}
</script>
