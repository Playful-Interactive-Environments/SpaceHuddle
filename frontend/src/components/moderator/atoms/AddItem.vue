<template>
  <el-card
    shadow="hover"
    class="add"
    :class="{ 'add--column': isColumn, readonly: !isClickable }"
    @click="$emit('addNew')"
  >
    <font-awesome-icon v-if="displayPlus" :icon="icon" />
    <span class="add__text">{{ text }}</span>
  </el-card>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class AddItem extends Vue {
  @Prop({ default: 'Add' }) text!: string;
  @Prop({ default: false }) isColumn!: boolean;
  @Prop({ default: true }) isClickable!: boolean;
  @Prop({ default: true }) displayPlus!: boolean;
  @Prop({ default: 'plus' }) icon!: boolean;
}
</script>

<style lang="scss" scoped>
.add {
  &--column {
    min-height: calc(110rem / 16);
    height: 100%;
  }
}

.readonly {
  &.add {
    cursor: unset;

    &:hover {
      background-color: inherit;
      border-style: dashed;
    }
  }
}
</style>
