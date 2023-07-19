<template>
  <ParticipantFindIt
    :taskId="taskId"
    :moduleId="moduleId"
    :useFullSize="useFullSize"
    :gameConfig="gameConfig"
    :backgroundClass="backgroundClass"
  />
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import ParticipantFindIt from '@/modules/information/findit/output/Participant.vue';
import gameConfig from '@/modules/information/forestfires/data/gameConfig.json';

@Options({
  computed: {
    gameConfig() {
      return gameConfig;
    },
  },
  components: {
    ParticipantFindIt,
  },
  emits: ['update:useFullSize'],
})

/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class Participant extends Vue {
  @Prop() readonly taskId!: string;
  @Prop() readonly moduleId!: string;
  @Prop({ default: false }) readonly useFullSize!: boolean;
  @Prop({ default: '' }) readonly backgroundClass!: string;
}
</script>

<style lang="scss" scoped></style>
