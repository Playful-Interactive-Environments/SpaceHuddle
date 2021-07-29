<template>
  TODO: implement voting content!
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {
  },
})
export default class ModeratorContentComponent extends Vue {
  @Prop() readonly taskId!: string;
}
</script>

<style scoped></style>
