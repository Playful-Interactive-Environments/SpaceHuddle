<template>
  <el-form-item>
    <el-button @click="console.log(this.formData)">click</el-button>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import * as moduleService from '@/services/module-service';
import { Module } from '@/types/api/Module';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import * as cashService from '@/services/cash-service';
import { Idea } from '@/types/api/Idea';
import * as viewService from '@/services/view-service';
import IdeaSortOrder from '@/types/enum/IdeaSortOrder';

@Options({
  components: {},
  emits: ['update'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;

  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  @Prop({ default: {} }) formData!: any;
  @Prop({ default: {} }) taskType!: any;
  module: Module | null = null;

  ideaGroups: Idea[][] = [];

  inputCash!: cashService.SimplifiedCashEntry<Idea[]>;

  @Watch('moduleId', { immediate: true })
  async onModuleIdChanged(): Promise<void> {
    if (this.moduleId) {
      moduleService.registerGetModuleById(
        this.moduleId,
        this.updateModule,
        EndpointAuthorisationType.MODERATOR,
        60 * 60
      );
    }
  }

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    console.log(this.modelValue);
  }

  updateModule(module: Module): void {
    this.module = module;
  }

  deregisterAll(): void {
    cashService.deregisterAllGet(this.updateModule);
  }

  unmounted(): void {
    this.deregisterAll();
  }
}
</script>
