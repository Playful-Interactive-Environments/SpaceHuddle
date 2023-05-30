<template>
  <el-form-item
    :label="$t('module.brainstorming.map.moderatorConfig.mapSection')"
    :prop="`${rulePropPath}.mapSection`"
  >
    <div style="height: 200px">
      <mapbox-map
        v-if="MapboxKey"
        :accessToken="MapboxKey"
        v-model:center="mapCenter"
        v-model:zoom="mapZoom"
        v-on:zoomend="changeSection"
        v-on:dragend="changeSection"
      >
      </mapbox-map>
    </div>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { MapboxMap } from 'vue-mapbox-ts';

@Options({
  components: { MapboxMap },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorConfig extends Vue {
  defaultFormRules: ValidationRuleDefinition = defaultFormRules;
  @Prop() readonly rulePropPath!: string;

  @Prop() readonly moduleId!: string;
  @Prop() readonly taskId!: string;
  @Prop() readonly topicId!: string;
  @Prop({ default: {} }) modelValue!: any;

  mapCenter = [14.511986682000128, 48.36875256196966];
  mapZoom = 5;

  get MapboxKey(): string {
    return process.env.VUE_APP_MAPBOX_KEY;
  }

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue && !this.modelValue.mapCenter) {
      this.modelValue.mapCenter = this.mapCenter;
    }
    if (this.modelValue && !this.modelValue.mapZoom) {
      this.modelValue.mapZoom = this.mapZoom;
    }
  }

  changeSection(): void {
    if (this.modelValue) {
      this.modelValue.mapCenter = this.mapCenter;
      this.modelValue.mapZoom = this.mapZoom;
    }
  }
}
</script>
