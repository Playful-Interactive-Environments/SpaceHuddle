<template>
  <div>
    <el-form-item
      :label="$t('module.playing.shopit.moderatorConfig.replayable')"
      :prop="`${rulePropPath}.replayable`"
    >
      <el-switch class="level-item" v-model="modelValue.replayable" />
    </el-form-item>
    <el-form-item
      :label="$t('module.playing.shopit.moderatorConfig.showTutorial')"
      :prop="`${rulePropPath}.showTutorial`"
    >
      <el-radio-group v-model="modelValue.showTutorial">
        <el-radio-button :label="0">{{
          $t('module.playing.shopit.moderatorConfig.showTutorialDisabled')
        }}</el-radio-button>
        <el-radio-button :label="1">{{
          $t('module.playing.shopit.moderatorConfig.showTutorialOnce')
        }}</el-radio-button>
        <el-radio-button :label="2">{{
          $t('module.playing.shopit.moderatorConfig.showTutorialAlways')
        }}</el-radio-button>
      </el-radio-group>
    </el-form-item>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

@Options({
  components: {
    FontAwesomeIcon,
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

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      if (!('replayable' in this.modelValue)) {
        this.modelValue.replayable = true;
      }
      if (!('showTutorial' in this.modelValue)) {
        this.modelValue.showTutorial = 1;
      }
    }
  }

  startPositionChanged(marker: any): void {
    const lngLat = marker.target._lngLat;
    this.modelValue.mapStart = [lngLat.lng, lngLat.lat];
  }
}
</script>

<style lang="scss" scoped></style>
