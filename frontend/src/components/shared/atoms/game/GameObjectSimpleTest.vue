<template>
  <container :x="x" :y="y" :rotation="rotation">
    <slot></slot>
  </container>
</template>

<script lang="ts">
export default {
  name: 'GameObjectSimpleTest',
  props: {
    x: Number,
    y: Number,
    rotation: Number,
  },
};
</script>
