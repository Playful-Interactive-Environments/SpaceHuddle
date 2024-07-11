<template>
  <div>test</div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { ResultValue } from '@/modules/information/personalityTest/types/ResultType';

@Options({
  components: {},
  emits: [],
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ParticipantResult extends Vue {
  @Prop() readonly test!: string;
  @Prop() readonly resultValue!: ResultValue;
}
</script>

<style lang="scss" scoped></style>
