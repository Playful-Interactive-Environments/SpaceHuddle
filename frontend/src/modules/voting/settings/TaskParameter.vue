<template>
  <el-form-item
    :label="$t('module.voting.settings.taskParameter.selection')"
    :prop="`${rulePropPath}.selectionId`"
    :rules="[defaultFormRules.ruleSelection]"
  >
    <el-select v-model="modelValue.selectionId" class="select--fullwidth">
      <el-option
        v-for="selection in selections"
        :key="selection.id"
        :value="selection.id"
        :label="selection.name"
      >
      </el-option>
    </el-select>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as taskService from '@/services/task-service';
import * as selectionService from '@/services/selection-service';
import { Task } from '@/types/api/Task';
import { Selection } from '@/types/api/Selection';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

@Options({
  components: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class TaskParameter extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;

  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  task: Task | null = null;
  selections: Selection[] = [];

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.selectionId) {
      this.modelValue.selectionId = null;
    }
  }

  async loadSelections(): Promise<void> {
    const topicId = this.task ? this.task.topicId : this.topicId;
    if (topicId) {
      await selectionService.getSelectionForTopic(topicId).then((selection) => {
        this.selections = selection;
      });
    }
  }

  @Watch('taskId', { immediate: true })
  async onTaskIdChanged(): Promise<void> {
    await this.getTask();
  }

  @Watch('topicId', { immediate: true })
  async onTopicIdChanged(): Promise<void> {
    await this.loadSelections();
  }

  async getTask(): Promise<void> {
    if (this.taskId) {
      await taskService.getTaskById(this.taskId).then((task) => {
        this.task = task;
      });
      await this.loadSelections();
    }
  }
}
</script>
