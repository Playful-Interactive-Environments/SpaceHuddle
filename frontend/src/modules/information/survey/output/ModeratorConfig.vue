<template>
  <CustomConfig
    ref="customConfig"
    :rulePropPath="rulePropPath"
    :moduleId="moduleId"
    :taskId="taskId"
    :topicId="topicId"
    v-model="modelValue"
    :limitQuestionType="QuestionType.SURVEY"
    @update="$emit('update')"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { QuestionnaireType } from '@/modules/information/quiz/types/QuestionnaireType';
import { CustomParameter, CustomSync } from '@/types/ui/CustomParameter';
import CustomConfig from '@/modules/information/quiz/organisms/CustomConfig.vue';

@Options({
  components: { CustomConfig },
  emits: ['update'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig
  extends Vue
  implements CustomParameter, CustomSync
{
  @Prop() readonly rulePropPath!: string;
  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  @Prop({ default: {} }) formData!: any;

  QuestionType = QuestionnaireType;

  async updateParameterForSaving(): Promise<void> {
    const customConfig = this.$refs.customConfig as CustomParameter;
    if (customConfig) await customConfig.updateParameterForSaving();
  }

  customSyncPublicParticipant(): boolean {
    const customConfig = this.$refs.customConfig as CustomSync;
    if (customConfig) return customConfig.customSyncPublicParticipant();
    return false;
  }
}
</script>
