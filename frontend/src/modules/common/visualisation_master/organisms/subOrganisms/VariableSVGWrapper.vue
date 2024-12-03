<template>
  <g>
    <slot />
  </g>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';

@Options({})
export default class VariableWrapper extends Vue {

}
</script>

<style lang="scss" scoped>

</style>
