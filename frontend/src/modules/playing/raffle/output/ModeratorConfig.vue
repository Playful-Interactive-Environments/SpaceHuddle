<template>
  <div>
    <el-form-item
      :label="$t('module.playing.raffle.moderatorConfig.condition')"
      :prop="`${rulePropPath}.condition`"
    >
      <el-select v-model="modelValue.condition">
        <el-option
          v-for="item of Object.values(RaffleCondition)"
          :key="item"
          :value="item"
          :label="$t(`module.playing.raffle.enum.raffleCondition.${item}`)"
        />
      </el-select>
    </el-form-item>
    <el-form-item
      :label="$t('module.playing.raffle.moderatorConfig.winText')"
      :prop="`${rulePropPath}.winText`"
    >
      <MarkdownEditor v-model="modelValue.winText" />
    </el-form-item>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import MarkdownEditor from '@/components/shared/molecules/MarkdownEditor.vue';
import { RaffleCondition } from '@/modules/playing/raffle/types/RaffleCondition';

@Options({
  components: {
    MarkdownEditor,
  },
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

  RaffleCondition = RaffleCondition;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      if (!('condition' in this.modelValue)) {
        this.modelValue.condition = RaffleCondition.PARTICIPATED;
      }
      if (!('winText' in this.modelValue)) {
        this.modelValue.winText = '';
      }
    }
  }
}
</script>

<style lang="scss" scoped></style>
