<script lang="ts">
import { Vue } from 'vue-class-component';
import { h, resolveComponent } from 'vue';
import { Prop } from 'vue-property-decorator';
import * as PIXI from 'pixi.js';
import {ContainerComponent, ContainerInst, ContainerEvents} from 'vue3-pixi/global';
import {Container, DisplayObject} from "pixi.js";
//const ContainerComponent = resolveComponent('container');

interface PixiEvents {
  added: [container: Container];
  childAdded: [child: DisplayObject, container: Container, index: number];
  childRemoved: [child: DisplayObject, container: Container, index: number];
  destroyed: [];
  removed: [container: Container];
  render: [ContainerInst];
}

type test = {
  [key in keyof PixiEvents as `on${Capitalize<key>}`]?:
  | ((...args: PixiEvents[key]) => any)
  | undefined;
};

///console.log(test);

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameObject extends Vue {
  //#region props
  @Prop({ default: 0 }) x!: number;
  @Prop({ default: 0 }) y!: number;
  @Prop({ default: 0 }) rotation!: number;
  //#endregion props
  container: PIXI.Container & EventTarget | null = null;

  mounted(): void {
    this.container = new PIXI.Text('test');
    this.container.x = this.x;
    this.container.y = this.y;
    (this.container as any).onRender = (container: Container): any => {
      console.log('render container', container);
    };
    //this.container.onAdded = this.container.added
    /*
        added: [container: Container];
    childAdded: [child: DisplayObject, container: Container, index: number];
    childRemoved: [child: DisplayObject, container: Container, index: number];
    destroyed: [];
    removed: [container: Container];
    render: [ContainerInst];
     */
  }

  render(): any {
    return h('container', this.$props);
    /*if (this.$slots.default)
      return h('container', this.$props, this.$slots.default());
    else return h('container', this.$props);*/
  }
}
</script>
