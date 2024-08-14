<template>
  <PublicScreen
    :task-id="taskId"
    :auth-header-typ="EndpointAuthorisationType.MODERATOR"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import EndpointAuthorisationType from '@/types/enum/EndpointAuthorisationType';
import PublicScreen from '@/modules/playing/raffle/output/PublicScreen.vue';

@Options({
  components: { PublicScreen },
})
export default class ModeratorContent extends Vue {
  @Prop() readonly taskId!: string;
  EndpointAuthorisationType = EndpointAuthorisationType;
}
</script>

<style lang="scss" scoped></style>
