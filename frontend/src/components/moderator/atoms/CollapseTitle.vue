<template>
  <span class="layout__level">
    <span v-if="displayAvatar" :style="{ color: avatar.color }">
      <font-awesome-icon :icon="avatar.symbol" />
    </span>
    <span v-else-if="color" :style="{ color: color }">
      {{ text.toUpperCase() }}
    </span>
    <span v-else>{{ text.toUpperCase() }}</span>
    <span v-on:click="$event.cancelBubble = true">
      <slot></slot>
    </span>
  </span>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Avatar } from '@/types/api/Participant';

@Options({
  components: {},
})
export default class CollapseTitle extends Vue {
  @Prop({ default: '' }) text!: string;
  @Prop({ default: null }) color!: string | null;
  @Prop({ default: null }) avatar!: Avatar | null;

  get displayAvatar(): boolean {
    return this.text == `${this.avatar?.symbol}${this.avatar?.color}`;
  }
}
</script>

<style lang="scss" scoped>
</style>
