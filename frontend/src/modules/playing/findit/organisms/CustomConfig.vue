<template>
  <el-form-item
    :label="$t('module.playing.findit.moderatorConfig.replayable')"
    :prop="`${rulePropPath}.replayable`"
  >
    <el-switch class="level-item" v-model="modelValue.replayable" />
  </el-form-item>
  <el-form-item
    :label="$t('module.playing.findit.moderatorConfig.showTutorialOnlyOnce')"
    :prop="`${rulePropPath}.showTutorialOnlyOnce`"
  >
    <el-switch class="level-item" v-model="modelValue.showTutorialOnlyOnce" />
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

@Options({
  components: {},
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class CustomConfig extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;

  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      if (!('replayable' in this.modelValue)) {
        this.modelValue.replayable = true;
      }
      if (!('showTutorialOnlyOnce' in this.modelValue)) {
        this.modelValue.showTutorialOnlyOnce = true;
      }
    }
  }
}
</script>

<style lang="scss" scoped>
.pin {
  --pin-color: var(--color-primary);
  font-size: var(--font-size-xxxlarge);
  color: var(--pin-color);
}
</style>
