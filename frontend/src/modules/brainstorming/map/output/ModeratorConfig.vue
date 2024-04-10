<template>
  <el-form-item
    :label="$t('module.brainstorming.map.moderatorConfig.mapSection')"
    :prop="`${rulePropPath}.mapSection`"
  >
    <div style="height: var(--map-settings-height)">
      <mgl-map
        :center="mapCenter"
        :zoom="mapZoom"
        :double-click-zoom="false"
        @map:zoomend="changeSection"
        @map:dragend="changeSection"
      >
        <mgl-navigation-control position="bottom-left" />
      </mgl-map>
    </div>
  </el-form-item>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { ValidationRuleDefinition, defaultFormRules } from '@/utils/formRules';
import { MglNavigationControl, MglMap } from 'vue-maplibre-gl';
import * as mapStyle from '@/utils/mapStyle';
import { defaultCenter } from '@/utils/map';

mapStyle.setMapStyleStreets();

@Options({
  components: {
    MglNavigationControl,
    MglMap,
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

  @Watch('modelValue', { immediate: true })
  async onModelValueChanged(): Promise<void> {
    if (this.modelValue) {
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
