<template>
  <div ref="container" />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { registerDomElement, unregisterDomElement } from '@/vunit';
import { PhysicBodies } from '@/modules/brainstorming/game/types/PhysicBodies';
import { AnimationTimeline } from '@/modules/brainstorming/game/types/AnimationTimeline';
import * as PIXI from 'pixi.js';
import { PixiBodies } from '@/modules/brainstorming/game/types/PixiBodies';

@Options({
  components: {},
  emits: [],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PixiCanvas extends Vue {
  @Prop() readonly physicBodies!: PhysicBodies;
  @Prop() readonly animationTimeline!: AnimationTimeline;
  app!: PIXI.Application;
  bodies!: PixiBodies;

  domKey = '';
  async mounted(): Promise<void> {
    const container = this.$refs.container as HTMLElement;
    this.domKey = registerDomElement(
      container,
      (targetWidth, targetHeight) => {
        this.app = new PIXI.Application({
          backgroundAlpha: 0,
          width: targetWidth,
          height: targetHeight,
        });
        container.appendChild(this.app.view as any);
        this.bodies = new PixiBodies(
          this.app,
          this.physicBodies,
          this.animationTimeline,
          targetWidth,
          targetHeight
        );
        this.physicBodies.addEvent('afterUpdate', this.update_drawing);
      },
      100,
      false
    );
  }

  async unmounted(): Promise<void> {
    unregisterDomElement(this.domKey);
  }

  async update_drawing(): Promise<void> {
    this.bodies.show();
  }
}
</script>
