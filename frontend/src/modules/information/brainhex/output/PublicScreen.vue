<template>
  <PlayerTypeResult :task-id="taskId" />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import PlayerTypeResult from '@/modules/information/brainhex/organisms/PlayerTypeResult.vue';

@Options({
  components: { PlayerTypeResult },
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class PublicScreen extends Vue {
  @Prop() readonly taskId!: string;
  @Prop({ default: EndpointAuthorisationType.MODERATOR })
  authHeaderTyp!: EndpointAuthorisationType;
}
</script>

<style lang="scss" scoped></style>
