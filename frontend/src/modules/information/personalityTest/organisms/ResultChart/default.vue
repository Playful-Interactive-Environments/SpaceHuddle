<template>
  <div></div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { ResultChartType } from '@/modules/information/personalityTest/types/ResultChartType';

@Options({
  components: {},
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ResultChart extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: ResultChartType.MODERATOR })
  readonly chartType!: ResultChartType;
}
</script>

<style lang="scss" scoped></style>
