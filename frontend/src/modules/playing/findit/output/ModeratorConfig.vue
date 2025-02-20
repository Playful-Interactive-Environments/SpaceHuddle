<template>
  <div>
    <el-form-item
      :label="$t('module.playing.findit.moderatorConfig.placeables')"
      :prop="`${rulePropPath}.placeables`"
    >
      <el-select v-model="modelValue.placeables">
        <el-option
          value=""
          :label="$t('module.playing.findit.moderatorConfig.all')"
        />
        <el-option
          v-for="configType of Object.keys(gameConfig)"
          :key="configType"
          :value="configType"
          :style="{
            color: getSettingsForLevelType(gameConfig, configType).color,
          }"
          :label="
            $t(
              `module.playing.findit.participant.placeables.${configType}.name`
            )
          "
        >
          <font-awesome-icon
            :icon="getSettingsForLevelType(gameConfig, configType).icon"
          />
          &nbsp;
          {{
            $t(
              `module.playing.findit.participant.placeables.${configType}.name`
            )
          }}
        </el-option>
      </el-select>
    </el-form-item>
    <el-form-item
      :label="$t('module.playing.findit.moderatorConfig.replayable')"
      :prop="`${rulePropPath}.replayable`"
    >
      <el-switch class="level-item" v-model="modelValue.replayable" />
    </el-form-item>
    <el-form-item
      :label="$t('module.playing.findit.moderatorConfig.showTutorial')"
      :prop="`${rulePropPath}.showTutorial`"
    >
      <el-radio-group v-model="modelValue.showTutorial">
        <el-radio-button :label="0">{{
          $t('module.playing.findit.moderatorConfig.showTutorialDisabled')
        }}</el-radio-button>
        <el-radio-button :label="1">{{
          $t('module.playing.findit.moderatorConfig.showTutorialOnce')
        }}</el-radio-button>
        <el-radio-button :label="2">{{
          $t('module.playing.findit.moderatorConfig.showTutorialAlways')
        }}</el-radio-button>
      </el-radio-group>
    </el-form-item>
  </div>
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
import gameConfig from '@/modules/playing/findit/data/gameConfig.json';
import * as configParameter from '@/utils/game/configParameter';

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
  gameConfig = gameConfig;

  getSettingsForLevel = configParameter.getSettingsForLevel;
  getSettingsForLevelType = configParameter.getSettingsForLevelType;

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      if (!('placeables' in this.modelValue)) {
        this.modelValue.placeables = '';
      }
      if (!('replayable' in this.modelValue)) {
        this.modelValue.replayable = true;
      }
      if (!('showTutorial' in this.modelValue)) {
        this.modelValue.showTutorial = 1;
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
          state: LevelWorkflowType.approved,
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
