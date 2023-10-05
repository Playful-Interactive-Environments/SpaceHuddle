<template>
  <div></div>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';
import { IModeratorContent } from '@/types/ui/IModeratorContent';

@Options({
  components: {},
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
