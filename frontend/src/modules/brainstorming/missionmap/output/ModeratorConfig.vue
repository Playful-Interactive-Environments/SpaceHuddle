<template>
  <div>
    <el-form-item
      :label="
        $t('module.brainstorming.missionmap.moderatorConfig.startingPoint')
      "
    >
    </el-form-item>
    <el-form-item
      v-for="parameter of ManuelInitParameter"
      :key="parameter"
      :label="$t(`module.brainstorming.missionmap.gameConfig.${parameter}`)"
      :prop="`${rulePropPath}.${parameter}`"
      :style="{ '--parameter-color': gameConfig.parameter[parameter].color }"
    >
      <template #label>
        {{ $t(`module.brainstorming.missionmap.gameConfig.${parameter}`) }}
        <font-awesome-icon :icon="gameConfig.parameter[parameter].icon" />
      </template>
      <el-slider
        v-if="modelValue"
        v-model="modelValue[parameter]"
        :min="-10"
        :max="10"
        show-stops
      />
    </el-form-item>
    <el-form-item
      :label="
        $t('module.brainstorming.missionmap.moderatorConfig.effectElectricity')
      "
      :prop="`${rulePropPath}.effectElectricity`"
    >
      <el-switch class="level-item" v-model="modelValue.effectElectricity" />
    </el-form-item>
    <el-form-item
      :label="
        $t('module.brainstorming.missionmap.moderatorConfig.minParticipants')
      "
      :prop="`${rulePropPath}.minParticipants`"
    >
      <template #label>
        {{
          $t('module.brainstorming.missionmap.moderatorConfig.minParticipants')
        }}
        (2 - 30)
      </template>
      <el-input-number
        v-model="modelValue.minParticipants"
        :min="2"
        :max="30"
        :step="1"
        step-strictly
        :value-on-clear="2"
      />
    </el-form-item>
    <el-form-item
      :label="$t('module.brainstorming.missionmap.moderatorConfig.minPoints')"
      :prop="`${rulePropPath}.minPoints`"
    >
      <template #label>
        {{ $t('module.brainstorming.missionmap.moderatorConfig.minPoints') }}
        (100 - {{ modelValue.maxPoints }})
      </template>
      <el-input-number
        v-model="modelValue.minPoints"
        :min="100"
        :max="modelValue.maxPoints"
        :step="100"
        step-strictly
        :value-on-clear="100"
      />
    </el-form-item>
    <el-form-item
      :label="$t('module.brainstorming.missionmap.moderatorConfig.maxPoints')"
      :prop="`${rulePropPath}.maxPoints`"
    >
      <template #label>
        {{ $t('module.brainstorming.missionmap.moderatorConfig.maxPoints') }}
        ({{ modelValue.minPoints }} - 10000)
      </template>
      <el-input-number
        v-model="modelValue.maxPoints"
        :min="modelValue.minPoints"
        :max="10000"
        :step="100"
        step-strictly
        :value-on-clear="modelValue.minPoints"
      />
    </el-form-item>
    <el-form-item
      :label="
        $t(
          'module.brainstorming.missionmap.moderatorConfig.allowParticipationMeasures'
        )
      "
      :prop="`${rulePropPath}.allowParticipationMeasures`"
    >
      <el-switch
        class="level-item"
        v-model="modelValue.allowParticipationMeasures"
      />
    </el-form-item>
    <el-form-item
      :label="$t('module.brainstorming.missionmap.moderatorConfig.theme')"
      :prop="`${rulePropPath}.theme`"
    >
      <el-select v-model="modelValue.theme">
        <el-option
          value=""
          :label="$t('module.brainstorming.missionmap.moderatorConfig.default')"
        />
        <el-option
          value="preparation"
          :label="
            $t('module.brainstorming.missionmap.moderatorConfig.preparation')
          "
        />
        <el-option
          value="meeting"
          :label="$t('module.brainstorming.missionmap.moderatorConfig.meeting')"
        />
      </el-select>
    </el-form-item>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { defaultFormRules, ValidationRuleDefinition } from '@/utils/formRules';
import { MglMap, MglNavigationControl } from 'vue-maplibre-gl';
import * as mapStyle from '@/utils/mapStyle';
import gameConfig from '@/modules/brainstorming/missionmap/data/gameConfig.json';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { defaultCenter } from '@/utils/map';
import AddItem from '@/components/moderator/atoms/AddItem.vue';
import { calculateMarks } from '@/utils/element-plus';

mapStyle.setMapStyleStreets();

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
  },
  components: {
    MglNavigationControl,
    MglMap,
    FontAwesomeIcon,
    AddItem,
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

  mapCenter = [...defaultCenter] as [number, number];
  mapZoom = 5;
  hasInput = false;
  calculateMarks = calculateMarks;

  get ManuelInitParameter(): string[] {
    /*if (this.hasInput && this.modelValue.insertInitProgressionFromInput)
      return [];*/
    return Object.keys(gameConfig.parameter);
  }

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
      for (const parameter of Object.keys(gameConfig.parameter)) {
        if (!(parameter in this.modelValue)) {
          this.modelValue[parameter] = 0;
        }
      }
      if (!('theme' in this.modelValue)) {
        this.modelValue.theme = '';
      }
      /*if (!('insertInitProgressionFromInput' in this.modelValue)) {
        this.modelValue.insertInitProgressionFromInput = true;
      }*/
      if (!('effectElectricity' in this.modelValue)) {
        this.modelValue.effectElectricity = false;
      }
      if (!('allowParticipationMeasures' in this.modelValue)) {
        this.modelValue.allowParticipationMeasures = false;
      }
      if (!('maxRatingStars' in this.modelValue)) {
        this.modelValue.maxRatingStars = 3;
      }
      if (!('minParticipants' in this.modelValue)) {
        this.modelValue.minParticipants = 3;
      }
      if (!('minPoints' in this.modelValue)) {
        this.modelValue.minPoints = 100;
      }
      if (!('maxPoints' in this.modelValue)) {
        this.modelValue.maxPoints = 1000;
      }
      if (!('explanationList' in this.modelValue)) {
        this.modelValue.explanationList = ['', '', ''];
      }
      if (!this.modelValue.mapCenter) {
        this.modelValue.mapCenter = this.mapCenter;
      } else {
        this.mapCenter = this.modelValue.mapCenter;
      }
      if (!this.modelValue.mapZoom) {
        this.modelValue.mapZoom = this.mapZoom;
      } else {
        this.mapZoom = this.modelValue.mapZoom;
      }
    }
  }

  @Watch('formData.input.length', { immediate: true })
  onInputChanged(): void {
    this.hasInput = this.formData.input.length > 0;
  }

  changeSection(event: any): void {
    if (this.modelValue) {
      const center = event.map.getCenter();
      this.mapCenter = [center.lng, center.lat];
      this.mapZoom = event.map.getZoom();
      this.modelValue.mapCenter = this.mapCenter;
      this.modelValue.mapZoom = this.mapZoom;
    }
  }
}
</script>

<style lang="scss" scoped>
.el-form-item::v-deep(.el-form-item__label) {
  color: var(--parameter-color);
}
</style>
