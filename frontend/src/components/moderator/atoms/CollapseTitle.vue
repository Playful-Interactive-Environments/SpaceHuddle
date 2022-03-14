<template>
  <span class="layout__level">
    <span v-if="displayAvatar">
      <span
        v-for="item in avatar"
        :key="item.symbol"
        :style="{ color: item.color }"
      >
        <font-awesome-icon :icon="item.symbol" />
        &nbsp;
      </span>
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
  @Prop({ default: null }) avatar!: Avatar[];

  get displayAvatar(): boolean {
    const avatarDisplayText = this.avatar
      .map((avatar) => `${avatar.symbol}${avatar.color}`)
      .join(' ');
    return this.text == avatarDisplayText;
  }
}
</script>

<style lang="scss" scoped></style>
