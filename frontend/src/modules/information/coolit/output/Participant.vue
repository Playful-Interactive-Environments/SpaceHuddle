<template>
  <div ref="gameContainer">
    <CoolIt v-if="displayGame" />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import CoolIt from '@/games/coolit/CoolIt.vue';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {
    CoolIt,
  },
  emits: ['update:useFullSize'],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
  displayGame = false;

  async mounted(): Promise<void> {
    //this.$emit('update:useFullSize', true);
    //this.$emit('update:drawNavigation', false);
    setTimeout(() => {
      const dom = this.$refs.gameContainer as HTMLElement;
      if (dom) {
        const targetWidth = dom.parentElement?.offsetWidth;
        const targetHeight = dom.parentElement?.offsetHeight;
        this.displayGame = true;
        if (targetWidth && targetHeight) {
          (dom as any).style.width = `${targetWidth}px`;
          (dom as any).style.height = `${targetHeight}px`;
          /*setTimeout(() => {
            const canvas = dom.getElementsByTagName('canvas')[0] as HTMLCanvasElement;
            if (canvas) {
              (canvas as any).style.width = `${targetWidth}px`;
              (canvas as any).style.height = `${targetHeight}px`;
              //canvas.width = targetWidth;
              //canvas.height = targetHeight;
            }
          }, 100);*/
        }
      }
    }, 100);
  }
}
</script>

<style lang="scss" scoped></style>
