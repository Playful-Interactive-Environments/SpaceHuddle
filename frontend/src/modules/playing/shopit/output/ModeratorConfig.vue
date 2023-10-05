<template>
  <el-form-item
    :label="$t('module.playing.shopit.moderatorConfig.replayable')"
    :prop="`${rulePropPath}.replayable`"
  >
    <el-switch class="level-item" v-model="modelValue.replayable" />
  </el-form-item>
  <el-form-item
    :label="$t('module.playing.shopit.moderatorConfig.showTutorialOnlyOnce')"
    :prop="`${rulePropPath}.showTutorialOnlyOnce`"
  >
    <el-switch class="level-item" v-model="modelValue.showTutorialOnlyOnce" />
  </el-form-item>
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
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue {
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

  startPositionChanged(marker: any): void {
    const lngLat = marker.target._lngLat;
    this.modelValue.mapStart = [lngLat.lng, lngLat.lat];
  }
}
</script>

<style lang="scss" scoped></style>
