<template>
  <DetailResult
    :task-id="taskId"
    :show-exception="chartType !== ResultChartType.PUBLIC"
  />
  <DetailResult
    v-if="chartType === ResultChartType.STATISTIC"
    :task-id="taskId"
    :show-all="true"
  />
  <DetailResult
    v-if="chartType === ResultChartType.STATISTIC"
    :task-id="taskId"
    :show-details="true"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import DetailResult from '@/modules/information/personalityTest/organisms/ResultChart/brainhex/DetailResult.vue';
import { ResultChartType } from '@/modules/information/personalityTest/types/ResultChartType';

@Options({
  components: { DetailResult },
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ResultChart extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: ResultChartType.MODERATOR })
  readonly chartType!: ResultChartType;

  ResultChartType = ResultChartType;
}
</script>

<style lang="scss" scoped></style>
