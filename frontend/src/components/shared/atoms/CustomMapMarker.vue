<template>
  <div ref="icon">
    <slot name="icon"> </slot>
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';
import {
  LngLatLike,
  Marker,
  MarkerOptions,
  PointLike,
  PositionAnchor,
  Map,
} from 'maplibre-gl';
import { mapSymbol } from 'vue-maplibre-gl';
import { inject, ShallowRef } from 'vue';

const MARKER_OPTION_KEYS: Array<keyof MarkerOptions> = [
  'element',
  'offset',
  'anchor',
  'color',
  'draggable',
  'clickTolerance',
  'rotation',
  'rotationAlignment',
  'pitchAlignment',
  'scale',
];

@Options({
  emits: ['dragend', 'click', 'update:pixelPos'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class CustomMapMarker extends Vue {
  @Prop() readonly coordinates!: LngLatLike;
  @Prop() readonly offset!: PointLike;
  @Prop() readonly anchor!: PositionAnchor;
  @Prop() readonly color!: string;
  @Prop() readonly clickTolerance!: number;
  @Prop() readonly rotation!: number;
  @Prop() readonly rotationAlignment!: 'map' | 'viewport' | 'auto';
  @Prop() readonly pitchAlignment!: 'map' | 'viewport' | 'auto';
  @Prop() readonly scale!: number;
  @Prop() readonly draggable!: boolean;
  @Prop() readonly pixelPos!: [number, number];
  @Prop({ default: false }) readonly hide!: boolean;
  marker: Marker | undefined = undefined;
  map!: ShallowRef<Map | null>;

  mounted(): void {
    // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
    this.map = inject(mapSymbol)!;
    if (this.$slots.icon) {
      setTimeout(() => {
        if (this.$slots.icon) {
          this.initMarker(this.$refs.icon as HTMLElement);
        }
      }, 500);
    } else {
      this.initMarker();
    }
  }

  initMarker(element: HTMLElement | null = null): void {
    const opts: MarkerOptions = Object.keys(this.$props)
      .filter(
        (opt) =>
          (this.$props as any)[opt] !== undefined &&
          MARKER_OPTION_KEYS.indexOf(opt as keyof MarkerOptions) !== -1
      )
      .reduce((obj, opt) => {
        (obj as any)[opt] = (this.$props as any)[opt];
        return obj;
      }, {});
    if (element) {
      opts.element = element.cloneNode(true) as HTMLElement;
      element.style.display = 'none';
    }
    const marker = new Marker(opts);
    // eslint-disable-next-line @typescript-eslint/no-non-null-assertion
    marker.setLngLat(this.coordinates).addTo(this.map.value!);
    marker.on('dragend', (event) => this.$emit('dragend', event));
    marker
      .getElement()
      .addEventListener('click', (event) => this.$emit('click', event));
    this.removeMarker();
    this.marker = marker;
  }

  removeMarker(): void {
    if (this.marker) {
      //this.marker.remove.bind(this.marker);
      this.marker.remove();
      this.marker = undefined;
    }
  }

  @Watch('coordinates', { immediate: true })
  onCoordinatesChanged(): void {
    if (this.marker) {
      this.marker.setLngLat(this.coordinates);
      this.$emit('update:pixelPos', [this.marker._pos.x, this.marker._pos.y]);
    }
  }

  @Watch('offset', { immediate: true })
  onOffsetChanged(): void {
    if (this.marker) {
      this.marker.setOffset(this.offset);
    }
  }

  @Watch('pitchAlignment', { immediate: true })
  onPitchAlignmentChanged(): void {
    if (this.marker) {
      this.marker.setPitchAlignment(this.pitchAlignment);
    }
  }

  @Watch('rotationAlignment', { immediate: true })
  onRotationAlignmentChanged(): void {
    if (this.marker) {
      this.marker.setRotationAlignment(this.rotationAlignment);
    }
  }

  @Watch('rotation', { immediate: true })
  onRotationChanged(): void {
    if (this.marker) {
      this.marker.setRotation(this.rotation);
    }
  }

  @Watch('hide', { immediate: true })
  onHideChanged(): void {
    if (this.marker) {
      if (this.hide) this.marker.getElement().style['display'] = 'none';
      else this.marker.getElement().style['display'] = 'block';
    }
  }

  unmounted(): void {
    this.removeMarker();
  }
}
</script>

<style lang="scss">
.maplibregl-marker-anchor-top {
  transform-origin: center top;
}
.maplibregl-marker-anchor-top-left {
  transform-origin: left top;
}
.maplibregl-marker-anchor-top-right {
  transform-origin: right top;
}
.maplibregl-marker-anchor-bottom {
  transform-origin: center bottom;
}
.maplibregl-marker-anchor-bottom-left {
  transform-origin: left bottom;
}
.maplibregl-marker-anchor-bottom-right {
  transform-origin: right bottom;
}
.maplibregl-marker-anchor-left {
  transform-origin: left center;
}
.maplibregl-marker-anchor-right {
  transform-origin: right center;
}
.maplibregl-marker-anchor-center {
  transform-origin: center center;
}
</style>

<style scoped lang="scss"></style>
