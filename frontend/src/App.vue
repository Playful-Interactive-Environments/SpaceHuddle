<template>
  <!--<router-view class="pre-formatted" />-->
  <router-view />
</template>

<script lang="ts">
import { Vue, Options } from 'vue-class-component';

@Options({
  components: {},
})
export default class App extends Vue {}
</script>
