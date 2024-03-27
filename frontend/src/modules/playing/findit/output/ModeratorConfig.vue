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
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import { CustomInit } from '@/types/ui/CustomParameter';
import defaultLevelConfig from '@/modules/playing/findit/data/defaultLevels.json';
import { LevelWorkflowType } from '@/types/game/LevelWorkflowType';
import * as ideaService from '@/services/idea-service';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';

@Options({
  components: {},
  emits: ['update'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue implements CustomInit {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;
  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;
  @Prop({ default: {} }) formData!: any;

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

  async initCreationData(taskId: string): Promise<void> {
    for (const key of Object.keys(defaultLevelConfig)) {
      const config = defaultLevelConfig[key];
      const addIdea: any = {
        keywords: key,
        description: '',
        link: null,
        image: null, // the datebase64 url of created image
        parameter: {
          state: LevelWorkflowType.created,
          type: config.type,
          items: structuredClone(config.items),
        },
      };
      await ideaService.postIdea(
        taskId,
        addIdea,
        EndpointAuthorisationType.MODERATOR
      );
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
