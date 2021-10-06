<template>
  <el-card class="filter_section">
    <slot></slot>
  </el-card>
</template>

<script lang="ts">
import { Options, Vue } from 'vue-class-component';

@Options({
  components: {},
})
export default class FilterSection extends Vue {}
</script>

<style lang="scss">
.filter_section {
  margin-bottom: 1rem;
  background-color: var(--color-darkblue);
  width: 100%;
  display: table;

  .el-card__body {
    display: table;
    width: 100%;
    padding: 0 1rem;

    > * {
      display: table-cell;
    }
  }

  label {
    width: 20%;
  }

  .heading {
    color: white;
  }
}
</style>
