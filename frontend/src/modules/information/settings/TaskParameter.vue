<template>
  <section>
    test information parameter
  </section>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import * as selectionService from '@/services/selection-service';
import { Task } from '@/types/api/Task';
import { maxLength, required } from '@vuelidate/validators';

@Options({
  components: {},
  validations: {
  },
})
export default class TaskParameter extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: {} }) modelValue!: any;
  task: Task | null = null;
  selectionName = '';

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    await this.getTask();
    if (this.task) {
      //info
      this.$emit('update:modelValue', this.task.parameter);
    }
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
    }
  }
}
</script>
