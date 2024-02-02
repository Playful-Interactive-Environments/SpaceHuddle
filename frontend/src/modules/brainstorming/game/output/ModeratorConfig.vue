<template>
  <el-form-item
    :label="$t('module.brainstorming.game.moderatorConfig.mode')"
    :prop="`${rulePropPath}.mode`"
  >
    <el-select v-model="modelValue.mode">
      <el-option
        v-for="mode in Object.keys(CanvasMode).filter(
          (item) => CanvasMode[item] !== CanvasMode.None
        )"
        :key="mode"
        :value="mode"
        :label="$t(`module.brainstorming.game.enum.mode.${CanvasMode[mode]}`)"
      >
      </el-option>
    </el-select>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';

export enum CanvasMode {
  None = 'None',
  Canvas = 'Canvas',
  Pixi = 'Pixi',
  PixiVue = 'PixiVue',
  GameEngineLite = 'GameEngineLite',
  GameEngine = 'GameEngine',
}

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

  CanvasMode = CanvasMode;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.mode) {
      this.modelValue.mode = CanvasMode.Canvas;
    }
  }
}
</script>
