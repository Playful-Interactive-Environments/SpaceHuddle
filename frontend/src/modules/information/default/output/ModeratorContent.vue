<template>TODO: implement information content!</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';
import { Prop } from 'vue-property-decorator';

@Options({
  components: {},
})
export default class ModeratorContent extends Vue {
  @Prop() readonly taskId!: string;
}
</script>

<style scoped></style>
