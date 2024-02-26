import {Options, Vue} from 'vue-class-component';
// eslint-disable-next-line @typescript-eslint/no-unused-vars
import { h, createRenderer, RendererOptions } from 'vue';
import { Prop } from 'vue-property-decorator';
import * as PIXI from 'pixi.js';
import {Container, Cursor, DisplayObject, EventMode, Filter, IHitArea, IPointData, MaskData, Rectangle} from "pixi.js";
import {ContainerInst, PixiEvents} from "vue3-pixi/global";
import {nodeOps, Renderer} from 'vue3-pixi';

@Options({
  name: 'GameObject',
  title: 'GameObject',
  id: 'GameObject',
  emits: ['added', 'childAdded', 'childRemoved', 'destroyed', 'removed'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class GameObject extends Vue implements PixiEvents {
  //#region props
  @Prop({ default: 0 }) x!: number;
  @Prop({ default: 0 }) y!: number;
  @Prop({ default: 0 }) rotation!: number;
  //#endregion props
  container: PIXI.Container | null = null;
  //container: PIXI.Container & EventTarget | null = null;
  renderer: any | null = null;
  name = 'GameObject';
  readonly _vp_name = 'GameObject';

  mounted(): void {
    /*this.container = new PIXI.Text('test');
    this.container.x = this.x;
    this.container.y = this.y;
    (this.container as any).onRender = (container: PIXI.Container): any => {
      console.log('render container', container);
    };*/
    const { createElement: createElement2, setText: setText2, ...nodeOps2 } = nodeOps;
    const prefix = 'pixi';
    const rendererOptions: RendererOptions<PIXI.Container, PIXI.Container> = {
      patchProp: (...args) => this.doNothing(...args),
      createElement: (...args) => createElement2(prefix, 'container'),
      setElementText: (...args) => setText2(prefix, ...args),
      setText: (...args) => setText2(prefix, ...args),
      //querySelector: (selector: string) => this.doNothing(selector),
      //setScopeId: (...args) => this.doNothing(...args),
      //cloneNode: (...args) => this.doNothing(...args),
      //insertStaticContent: (...args) => this.doNothing(...args),
      ...nodeOps2,

      /*insert(el: HostNode, parent: HostElement, anchor?: HostNode | null): void;
      remove(el: HostNode): void;
      createElement(type: string, isSVG?: boolean, isCustomizedBuiltIn?: string, vnodeProps?: (VNodeProps & {
        [key: string]: any;
      }) | null): HostElement;
      createText(text: string): HostNode;
      createComment(text: string): HostNode;
      setText(node: HostNode, text: string): void;
      setElementText(node: HostElement, text: string): void;
      parentNode(node: HostNode): HostElement | null;
      nextSibling(node: HostNode): HostNode | null;
      querySelector?(selector: string): HostElement | null;
      setScopeId?(el: HostElement, id: string): void;
      cloneNode?(node: HostNode): HostNode;
      insertStaticContent?(content: string, parent: HostElement, anchor: HostNode | null, isSVG: boolean, start?: HostNode | null, end?: HostNode | null): [HostNode, HostNode];
*/
    };
    this.renderer = createRenderer<PIXI.Container, PIXI.Container>(rendererOptions);
  }

  createElement(...args): any {
    console.log(args);
    this.container = new PIXI.Container();
    this.container.x = this.x;
    this.container.y = this.y;
    return this.container;
  }

  doNothing(...args): any {
    return null;
  }

  onAdded(container: Container): any {
    console.log('on added');
  }

  onChildAdded(child: DisplayObject, container: Container, index: number): any {
    console.log('on child added');
  }

  onChildRemoved(child: DisplayObject, container: Container, index: number): any {
    console.log('on child added');
  }

  onDestroyed(): any {
    console.log('on destroyed');
  }

  onRemoved(container: Container): any {
    console.log('on removed');
  }

  onRender(container: ContainerInst): any {
    console.log('on render');
  }

  addEventListener(type, listener, options): void {
    console.log('add event listener');
  }

  removeEventListener(type, listener, options): void {
    console.log('remove event listener');
  }

  dispatchEvent(event): void {
    console.log('dispatche event listener');
  }

  added: [container: PIXI.Container] = [new PIXI.Container()];
  childAdded!: [child: PIXI.DisplayObject, container: PIXI.Container, index: number];
  childRemoved!: [child: PIXI.DisplayObject, container: PIXI.Container, index: number];
  destroyed!: [];
  removed!: [container: PIXI.Container];

  /*added(container: Container): void {
    console.log('added');
  }

  childAdded(child: DisplayObject, container: Container, index: number): any {
    console.log('child added');
  }

  childRemoved(child: DisplayObject, container: Container, index: number): any {
    console.log('child added');
  }

  destroyed(): any {
    console.log('destroyed');
  }

  removed(container: Container): any {
    console.log('removed');
  }*/

  render(): any {
    /*const { createElement, setText, ...nodeOps } = _nodeOps
    const { prefix = 'pixi' } = options
    const rendererOptions = rendererWithCapture({
      // @ts-expect-error
      createElement: (...args) => createElement(prefix, ...args),
      setElementText: (...args) => setText(prefix, ...args),
      setText: (...args) => setText(prefix, ...args),
      patchProp,
      ...nodeOps,
    })*/
    //return this.renderer;
    //return h('container', this.$props, this.$slots);
  }

  /*render(container: ContainerInst): any {
    console.log('render');
  }

  render(): any {
    if (this.container) {
      this.container.x = this.x;
      this.container.y = this.y;
      //return this.container.render();
    }
    //return h('container', this.$props, this.$slots);
    //return this.container;
    //if (this.container)
    //return h(this.container as any, this.$props, this.$slots);
  }*/
}
