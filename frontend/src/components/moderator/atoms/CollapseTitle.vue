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
    <span v-else>
      <el-tooltip placement="top" :content="text">
        {{ text.toUpperCase() }}
      </el-tooltip>
    </span>
    <span v-on:click="$event.cancelBubble = true">
      <slot></slot>
    </span>
  </span>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { Avatar, convertAvatarToString } from '@/types/api/Participant';

@Options({
  components: {},
})
export default class CollapseTitle extends Vue {
  @Prop({ default: '' }) text!: string;
  @Prop({ default: null }) color!: string | null;
  @Prop({ default: null }) avatar!: Avatar[];

  get displayAvatar(): boolean {
    if (this.avatar) {
      const avatarDisplayText = this.avatar
        .map((avatar) => convertAvatarToString(avatar))
        .join(' ');
      return this.text == avatarDisplayText;
    }
    return false;
  }
}
</script>

<style lang="scss" scoped>
.layout__level {
  display: -webkit-box;
  line-clamp: 1;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>
