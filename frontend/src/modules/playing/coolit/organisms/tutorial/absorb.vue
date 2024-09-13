<template>
  <div></div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop, Watch } from 'vue-property-decorator';

@Options({
  components: {},
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class absorb extends Vue {
  @Prop() readonly id!: string;

  mounted(): void {
    //todo
  }

  @Watch('id', { immediate: true })
  onIdChanged(): void {
    //todo
  }
}
</script>

<style lang="scss" scoped></style>
