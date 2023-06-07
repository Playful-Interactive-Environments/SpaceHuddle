<template>
  <div ref="mapSpace" class="mapSpace">
    <mgl-map
      v-if="showMap"
      ref="map"
      :center="mapCenter"
      :zoom="mapZoom"
      language="en"
      @map:load="onLoad"
    >
      <CustomMapMarker
        :coordinates="convertCoordinates(idea.parameter.position)"
        :draggable="canChangePosition"
        v-for="idea of ideas"
        :key="idea.id"
        v-on:dragend="(marker) => ideaPositionChanged(marker, idea)"
        v-on:click="ideaSelected(idea)"
        color="#cc0000"
        :scale="0.5"
      >
        <template v-slot:icon>
          <el-tooltip placement="top" effect="light" :hide-after="0">
            <template #content>
              <IdeaCard
                style="max-width: 10rem"
                :idea="idea"
                :is-editable="false"
              />
            </template>
            <font-awesome-icon
              icon="location-dot"
              class="pin"
              :style="{
                '--pin-color':
                  idea.id === selectedIdeaId
                    ? MarkerColorSelected
                    : MarkerColor,
              }"
            />
          </el-tooltip>
          <el-avatar
            v-if="idea.image"
            :size="20"
            :src="idea.image"
            :alt="idea.keywords"
            class="pin-image"
          />
          <el-avatar
            v-else-if="idea.link"
            :size="20"
            :src="idea.link"
            :alt="idea.keywords"
            class="pin-image"
          />
        </template>
      </CustomMapMarker>
      <mgl-navigation-control position="bottom-left" />
    </mgl-map>

    <div class="overlay">
      <div class="el-dropdown-link" v-on:click="calculateMapBounds">
        <font-awesome-icon icon="arrows-to-circle" />
      </div>
      <el-dropdown v-on:command="mapstyleChange($event)">
        <div class="el-dropdown-link">
          <font-awesome-icon icon="map" />
        </div>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item
              v-for="mapType in Object.values(MapStyles)"
              :key="mapType"
              :command="mapType"
            >
              <img width="50" :src="getPreviewUrl(mapType)" alt="mapType" />
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { Idea } from '@/types/api/Idea';
import IdeaCard from '@/components/moderator/organisms/cards/IdeaCard.vue';
import {
  MglDefaults,
  MglNavigationControl,
  MglMap,
  MglEvent,
} from 'vue-maplibre-gl';
import { LngLatLike, LngLatBoundsLike, Map } from 'maplibre-gl';
import CustomMapMarker from '@/components/shared/atoms/CustomMapMarker.vue';
import * as turf from '@turf/turf';

export enum MapStyles {
  STREETS = 'streets-v2',
  SATELLITE = 'hybrid',
  BASIC = 'basic-v2',
  OUTDOORS = 'outdoor-v2',
  LIGHT = 'dataviz-light',
  DARK = 'dataviz-dark',
  WINTER = 'winter-v2',
}

@Options({
  components: {
    CustomMapMarker,
    IdeaCard,
    FontAwesomeIcon,
    MglNavigationControl,
    MglMap,
  },
  emits: [
    'ideaPositionChanged',
    'update:selectedIdea',
    'visibleIdeasChanged',
    'selectionColorChanged',
  ],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class IdeaMap extends Vue {
  @Prop({ default: [] }) readonly ideas!: Idea[];
  @Prop({ default: {} }) readonly parameter!: any;
  @Prop({ default: true }) readonly canChangePosition!: boolean;
  @Prop({ default: true }) readonly calculateSize!: boolean;
  @Prop({ default: null }) readonly selectedIdea!: Idea | null;
  visibleIdeas: Idea[] = [];

  mapCenter: [number, number] = [0, 0];
  mapIdeaCenter: [number, number] = [0, 0];
  mapZoom = 14;
  mapBounds: LngLatBoundsLike | null = null;
  sizeCalculated = false;
  map!: Map;

  MapStyles = MapStyles;
  mapStyle: string = MapStyles.STREETS;

  showMap = true;

  get MarkerColor(): string {
    switch (this.mapStyle) {
      case MapStyles.LIGHT:
      case MapStyles.OUTDOORS:
      case MapStyles.STREETS:
      case MapStyles.BASIC:
      case MapStyles.WINTER:
        return '#1d2948';
      case MapStyles.DARK:
        return '#67c2d0';
      case MapStyles.SATELLITE:
        return 'white';
    }
    return '#1d2948';
  }

  get MarkerColorSelected(): string {
    switch (this.mapStyle) {
      case MapStyles.OUTDOORS:
        return '#fe6e5d';
      case MapStyles.LIGHT:
      case MapStyles.STREETS:
      case MapStyles.DARK:
      case MapStyles.SATELLITE:
      case MapStyles.BASIC:
      case MapStyles.WINTER:
        return '#f3a40a';
    }
    return '#f3a40a';
  }

  getPreviewUrl(key: string): string {
    switch (key) {
      case MapStyles.STREETS:
        key = 'streets-v2';
        break;
      case MapStyles.SATELLITE:
        key = 'hybrid';
        break;
      case MapStyles.BASIC:
        key = 'basic-v2';
        break;
      case MapStyles.OUTDOORS:
        key = 'outdoor';
        break;
      case MapStyles.LIGHT:
        key = 'streets-v2-light';
        break;
      case MapStyles.DARK:
        key = 'streets-v2-dark';
        break;
      case MapStyles.WINTER:
        key = 'winter';
        break;
    }
    return `https://www.maptiler.com/img/cloud/slider/${key}.png`;
  }

  get StyleUrl(): string {
    /*switch (this.mapStyle) {
      case MapStyles.DARK:
        return '/assets/map/maplibre-gl-styles-main/dark-matter/style-local.json';
    }*/
    return `https://api.maptiler.com/maps/${this.mapStyle}/style.json?key=${process.env.VUE_APP_MAPTILER_KEY}`;
  }

  mounted(): void {
    MglDefaults.style = this.StyleUrl;
    if (this.calculateSize) {
      setTimeout(() => {
        const dom = this.$refs.mapSpace as HTMLElement;
        if (dom) {
          const targetWidth = dom.parentElement?.offsetWidth;
          const targetHeight = dom.parentElement?.offsetHeight;
          if (targetWidth && targetHeight) {
            (dom as any).style.width = `${targetWidth}px`;
            (dom as any).style.height = `${targetHeight}px`;
          }
          this.sizeCalculated = true;
        }
      }, 500);
    }
  }

  convertCoordinates(position: [number, number]): LngLatLike {
    return {
      lng: position[0],
      lat: position[1],
    };
  }

  @Watch('parameter.mapCenter', { immediate: true })
  @Watch('parameter.mapZoom', { immediate: true })
  onParameterChanged(): void {
    if (this.parameter.mapCenter) {
      this.mapCenter = this.parameter.mapCenter;
    }
    if (this.parameter.mapZoom) {
      this.mapZoom = this.parameter.mapZoom;
    }
    this.fitZoomToBounds();
  }

  @Watch('ideas', { immediate: true, deep: true })
  @Watch('ideas.length', { immediate: true })
  onIdeasChanged(): void {
    this.calculateMapBounds();
  }

  @Watch('selectedIdea', { immediate: true })
  onSelectedIdeaIdChanged(): void {
    if (this.selectedIdea) {
      if (this.selectedIdeaId !== this.selectedIdea.id) {
        this.selectedIdeaId = this.selectedIdea.id;
        if (this.map) {
          this.map.setCenter(
            this.convertCoordinates(this.selectedIdea.parameter)
          );
          this.changeSection();
        }
      }
    } else this.selectedIdeaId = '';
  }

  calculateMapBounds(): void {
    if (this.ideas.length > 0) {
      const points = turf.points(
        this.ideas.map((idea) => idea.parameter.position)
      );
      const center: [number, number] = turf.center(points).geometry
        .coordinates as [number, number];

      if (this.ideas.length > 1) {
        const bounds = turf.envelope(points).geometry.coordinates[0] as [
          number,
          number
        ][];
        const min = bounds[0];
        const max = bounds[2];
        const delta = 0.02;
        const minLngLat = this.convertCoordinates([
          min[0] - delta,
          min[1] - delta,
        ]);
        const maxLngLat = this.convertCoordinates([
          max[0] + delta,
          max[1] + delta,
        ]);
        this.mapBounds = [minLngLat, maxLngLat];
        this.fitZoomToBounds();
      } else this.mapBounds = null;
      this.mapIdeaCenter = center;
      this.changeSection();
    } else this.mapBounds = null;
  }

  selectedIdeaId = '';
  ideaSelected(idea: Idea): void {
    this.selectedIdeaId = idea.id;
    this.$emit('update:selectedIdea', idea);
  }

  ideaPositionChanged(marker: any, idea: Idea): void {
    const lngLat = marker.target._lngLat;
    if (
      idea.parameter.position[0] !== lngLat.lng ||
      idea.parameter.position[1] !== lngLat.lat
    ) {
      idea.parameter.position = [lngLat.lng, lngLat.lat];
      this.$emit('ideaPositionChanged', idea);
    }
  }

  fitZoomToBounds(waiteForLoading = true): void {
    if (this.map && this.mapBounds) {
      this.map.fitBounds(this.mapBounds);
    } else if (this.map) {
      this.map.setZoom(this.mapZoom);
      if (
        this.ideas.length > 0 &&
        this.mapIdeaCenter[0] !== 0 &&
        this.mapIdeaCenter[1] !== 0
      )
        this.map.setCenter(this.convertCoordinates(this.mapIdeaCenter));
      else this.map.setCenter(this.convertCoordinates(this.mapCenter));
    }

    if (waiteForLoading) {
      setTimeout(() => {
        this.fitZoomToBounds(false);
      }, 300);
    }
  }

  onLoad(e: MglEvent): void {
    this.map = e.map;
    this.fitZoomToBounds();
    this.mapstyleChange(this.mapStyle);
  }

  mapstyleChange(command: string): void {
    this.mapStyle = command;
    if (this.map) {
      this.map.setStyle(this.StyleUrl);
      setTimeout(() => {
        if (this.map) {
          const notNeededLayers = this.map.getStyle().layers.filter((layer) => {
            const layerCategory = layer['source-layer'];
            const layerType = layer['type'];
            if (layerCategory) {
              return layerType === 'symbol' && layerCategory !== 'place';
            }
            return false;
          });
          for (const layer of notNeededLayers) {
            this.map.removeLayer(layer.id);
          }
        }
      }, 500);
    }
    this.$emit('selectionColorChanged', this.MarkerColorSelected);
  }

  changeSection(): void {
    const oldVisibleIdeas = [...this.visibleIdeas];
    let visibilityAdded = false;
    if (this.map) {
      const visibleIdeas: Idea[] = [];
      const bounds = this.map.getBounds();
      for (const idea of this.ideas) {
        if (bounds.contains(this.convertCoordinates(idea.parameter.position))) {
          visibleIdeas.push(idea);
          if (!oldVisibleIdeas.find((old) => old.id === idea.id))
            visibilityAdded = true;
        }
      }
      this.visibleIdeas = visibleIdeas;
    } else this.visibleIdeas = [...this.ideas];
    if (
      oldVisibleIdeas.length !== this.visibleIdeas.length ||
      visibilityAdded
    ) {
      this.$emit('visibleIdeasChanged', this.visibleIdeas);
    }
  }
}
</script>

<style lang="scss" scoped>
@import '~maplibre-gl/dist/maplibre-gl.css';
@import '~vue-maplibre-gl/dist/vue-maplibre-gl.css';

.mapSpace {
  position: relative;
  height: 100%;
  width: 100%;

  .overlay {
    background-color: white;
    border-radius: 0.5rem;
    position: absolute;
    z-index: 100;
    top: 0.5rem;
    right: 0.5rem;
    cursor: pointer;
  }

  .el-dropdown-link {
    background-color: white;
    border-radius: 50%;
    padding: 0.5rem;
  }

  .pin {
    --pin-color: var(--color-primary);
    font-size: var(--font-size-xxxlarge);
    color: var(--pin-color);
  }

  .pin-image {
    position: relative;
    left: -1.45rem;
    top: -0.5rem;
  }
}
</style>
