<template>
  <div>
    <Highscore :task-id="taskId" />
  </div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { IModeratorContent } from '@/types/ui/IModeratorContent';
import Highscore from '@/modules/playing/shopit/organisms/Highscore.vue';

@Options({
  components: { Highscore },
})
/* eslint-disable @typescript-eslint/no-explicit-any*/
export default class ModeratorContent extends Vue implements IModeratorContent {
  @Prop() readonly taskId!: string;

  reloadTaskSettings(): void {
    //
  }
}
</script>

<style scoped></style>
